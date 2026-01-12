<?php

declare(strict_types = 1);

namespace App\Lib\Emails;

use App\Lib\I18n\LegacyI18n;
use App\Model\Entity\User;
use Cake\Http\Exception\InternalErrorException;
use Cake\Mailer\Mailer;
use function Cake\I18n\__ as __;
use function Cake\I18n\__d as __d;

abstract class EmailBase implements \JsonSerializable
{
    const ALL = 0;
    const HIDE_BUTTON = ' ';
    const string SKIP_SEND_EMAIL_ADDRESS = 'skip-send@example.com';

    protected User $dearUser;
    private ?string $_locale = null;
    protected ?array $_attachments = null;
    protected ?array $_bccEmails = null;
    protected bool $_forceSend = false;
    private bool $_includeBccInSubject = false;

    public function __construct(User $user)
    {
        $this->dearUser = new User();
        $this->dearUser->id = $user->id;
        $this->dearUser->email = $user->email;
        //$this->dearUser->gender = $user->gender;
        $this->dearUser->last_name = $user->last_name;
        $this->dearUser->first_name = $user->first_name;
        //$this->dearUser->language_id = $user->language_id;
    }


    public function setAttachments(array $attachments): self
    {
        $this->_attachments = $attachments;
        return $this;
    }

    public function setForceSend(bool $forceSend): self
    {
        $this->_forceSend = $forceSend;
        return $this;
    }

    public function setIncludeBccInSubject(bool $includeBccInSubject): EmailBase
    {
        $this->_includeBccInSubject = $includeBccInSubject;
        return $this;
    }

    public function setBccEmails(array $emails): EmailBase
    {
        $this->_bccEmails = $emails;
        return $this;
    }

    protected abstract function getName(): string;

    protected abstract function getDescription(): ?string;

    protected abstract function getRecipient(): int;

    protected abstract function getSubject(): string;

    protected abstract function getNotifAction(): array;

    protected abstract function getCallToActionHref(): string;

    protected abstract function getCallToActionLabel(): string;

    protected abstract function _getI18n(): TranslatedString;

    protected abstract function _getType(): string;

    protected function getHeader(): ?string
    {
        return null;
    }

    protected function getFinalText(): string
    {
        return '';
    }

    protected function getFinalPic(): ?string
    {
        return null;
    }

    protected function getNotifSeller(): ?string
    {
        return null;
    }

    protected function getFromSellerId(): ?string
    {
        return null;
    }

    protected function _getTranslatedSubject(bool $withoutArgs = false): string
    {
        return $this->_translateGeneral($this->getSubject(), $withoutArgs);
    }

    protected function _getTranslatedFinalText(bool $withoutArgs = false): string
    {
        return $this->_translateGeneral($this->getFinalText(), $withoutArgs);
    }

    protected function _getTranslatedHeader(bool $withoutArgs = false): string
    {
        return $this->_translateGeneral('' . $this->getHeader(), $withoutArgs);
    }

    private function _translateGeneral(string $singular, bool $withoutArgs = false): string
    {
        if ($withoutArgs) {
            return __($singular);
        } else {
            return __($singular, ...$this->_allI18nArgs());
        }
    }

    protected function _getTranslatedCallToActionLabel(): string
    {
        return $this->getCallToActionLabel();
    }

    protected function _getTranslatedBody(): TranslatedString
    {
        $body = $this->_getI18n();
        $entity = $this->_translateFromDb();
        if ($entity) {
            $body->overwriteLang($this->_locale, $entity->email_body);
        }
        return $body;
    }

    protected function _isDisabled(): bool
    {
        $entity = $this->_translateFromDb();
        if ($entity) {
            return (bool)$entity->is_disabled;
        }
        return false;
    }

    protected function _hasAttachmentDisabled(): bool
    {
        $entity = $this->_translateFromDb();
        if ($entity) {
            return (bool)$entity->attachments_disabled;
        }
        return false;
    }

    protected function _bccEmailAddress(): ?string
    {
        $entity = $this->_translateFromDb();
        if ($entity) {
            return $entity->bcc;
        }
        return null;
    }

    public function send(bool $onlydebug = false): bool
    {
        $oldLocale = LegacyI18n::getLocale();
        //LegacyI18n::setLocale($this->dearUser->getLang3letter());
        $email = $this->dearUser->email;
        $subject = $this->_getTranslatedSubject();
        $isDisabled = $this->_isDisabled() && !$this->_forceSend;
        if ($isDisabled) {
            if ($onlydebug) {
                debug('Email is disabled. Subject: ' . $subject . ' ' . $email);
            }
            return false;
        }
        $viewVars = $this->_getViewVars();
        if ($this->_hasAttachmentDisabled()) {
            $attachments = null;
        } else {
            $attachments = $this->_attachments;
        }
        if (!$isDisabled) {
            $toRet = $this->_sendEmail($email, $subject, $viewVars, $attachments);
        }
        LegacyI18n::setLocale($oldLocale);
        return $toRet;
    }

    protected function _sendEmail(
        string $recipient,
        string $subject,
        array $viewVars,
        ?array $attachments,
        bool $onlyDebug = false
    ): bool {
        if ($onlyDebug) {
            debug([$recipient, $subject, $viewVars, $attachments]);
            return false;
        } else {
            $mailer = new Mailer('default');
            $mailer = $mailer->setTo($recipient)
                ->setSubject($subject)
                ->setEmailFormat('html')
                ->setViewVars($viewVars);
            if ($attachments) {
                $mailer->setAttachments($attachments);
            }
            $mailer->getRenderer()->viewBuilder()->setTemplate('default');
            if ($recipient === self::SKIP_SEND_EMAIL_ADDRESS) {
                return true;
            } else {
                $sent = $mailer->send();
            }
            return (bool)$sent;
        }
    }

    public function sendOrFail(): void
    {
        if (!$this->_isDisabled() && !$this->send()) {
            throw new InternalErrorException('Error enqueuing email');
        }
    }

    protected function i18nValues(): array
    {
        return [];
    }

    protected function _messageConcat(): string
    {
        return '';
    }

    public function _getViewVars(): array
    {
        $viewVars = [
            'header' => $this->_getTranslatedHeader(),
            'message' => $this->__e() . $this->_messageConcat(),
            'notif_type' => $this->_getType(),
            'notif_action' => $this->getNotifAction()
        ];
        $ctaLabel = $this->_getTranslatedCallToActionLabel();
        if ($ctaLabel && $ctaLabel != self::HIDE_BUTTON) {
            $viewVars['cta_label'] = $ctaLabel;
            $viewVars['cta_href'] = $this->getCallToActionHref();
        }
        $finalText = $this->_getTranslatedFinalText();
        if ($finalText) {
            $viewVars['final_text'] = $finalText;
        }
        $finalPic = $this->getFinalPic();
        if ($finalPic !== null) {
            $viewVars['final_pic'] = $finalPic;
        }
        $notifSeller = $this->getNotifSeller();
        if ($notifSeller !== null) {
            $viewVars['notif_seller'] = $notifSeller;
        }
        $fromSellerId = $this->getFromSellerId();
        if ($fromSellerId !== null) {
            $viewVars['from_seller_id'] = $fromSellerId;
        }
        $bcc = $this->_bccEmailAddress();
        if ($bcc || $this->_bccEmails) {
            $dbBcc = $bcc ? [$bcc] : [];
            $bccEmails = $this->_bccEmails ?? [];
            $bccEmails = array_merge($dbBcc, $bccEmails);
            $viewVars['bcc_emails'] = $bccEmails;
        }
        return $viewVars;
    }

    protected function __e(): string
    {
        $args = [$this->dearUser];
        foreach ($this->i18nValues() as $value) {
            $args[] = $value;
        }
        return $this->_translateEmail($this->_getTranslatedBody(), ...$args);
    }

    private function _translateEmail(...$args): string
    {
        $firstArg = $args[0] ?? null;
        if (!$firstArg) {
            throw new InternalErrorException('First argument has to be provided');
        }
        if (is_string($firstArg)) {
            $translatedString = new TranslatedString($firstArg);
        } else if ($firstArg instanceof TranslatedString) {
            $translatedString = $firstArg;
        } else {
            throw new InternalErrorException('First argument has to be TranslatedString or string');
        }
        $user = $args[1] ?? null;
        if (!$user || !$user instanceof User) {
            throw new InternalErrorException('Second argument has to be a User');
        }
        $args = array_merge([$user->first_name, $user->last_name], array_slice($args, 2));

        $translator = LegacyI18n::getTranslator();
        $translated = $this->_getTranslatedWithGender($translatedString, $user->gender);
        return $translator->translate($translated, $args);
    }

    private static function _getTranslatedWithGender(TranslatedString $translatedString, $gender): string
    {
        return $translatedString->getTranslation();
    }

    private function _translateFromDb(): null
    {
        return null;
    }

    private function _allI18nArgs(): array
    {
        $args = [$this->dearUser->first_name, $this->dearUser->last_name];
        foreach ($this->i18nValues() as $value) {
            $args[] = $value;
        }
        return $args;
    }

    private function _allI18nArgsTitles(): array
    {
        $custom = $this->customI18nValueTitles();
        array_unshift($custom, __('Last name'));
        array_unshift($custom, __('First name'));
        return $custom + $this->_allI18nArgs();
    }

    protected function customI18nValueTitles(): array
    {
        return [];
    }

    public function toArray(): array
    {
        $res = [
            'attachments_disabled' => $this->_hasAttachmentDisabled(),
            'email_type' => $this->_getType(),
            'is_disabled' => $this->_isDisabled(),
            'readable_name' => $this->getName(),
            'readable_description' => $this->getDescription(),
            'recipient' => $this->getRecipient(),
            'locale' => LegacyI18n::getLocale(),
            'subject' => $this->_getTranslatedSubject(true),
            'cta_label' => $this->_getTranslatedCallToActionLabel(),
            'header' => $this->_getTranslatedHeader(true),
            'final_text' => $this->_getTranslatedFinalText(true),
            'translation' => $this->_getI18n()->getKey(),
            'email_body' => $this->_getTranslatedBody()->getTranslation(),
            'email_body_args' => $this->_allI18nArgs(),
            'email_body_args_titles' => $this->_allI18nArgsTitles(),
            'bcc' => $this->_bccEmailAddress(),
        ];
        return $res;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
