<tbody>
<tr>
    <td align="left"
        style="font-size: 0px; padding: 60px 56px 40px">
        <div
            style="font-family: Arial; font-size: 38px; font-weight: 700; text-align: left; color: rgb(124, 124, 124); line-height: 44px">
            <?php if (isset($header)) {
                echo $header;
            } ?>
    </td>
</tr>
<tr>
    <td align="left"
        style="font-size: 0px; padding: 0 56px">
        <div
            style="font-family: Arial; font-size: 20px; text-align: left; color: rgb(92, 94, 98); line-height: 26px">
            <?= $message ?>
        </div>
    </td>
</tr>
<tr>
    <td style=""></td>
</tr>
<?php if (isset($cta_href)) { ?>
    <tr>
        <td align="left"
            style="box-sizing: border-box; font-size: 0px; padding: 40px 56px 0">
            <table border="0" cellpadding="0" cellspacing="0"
                   style="border-collapse: separate; line-height: 100%">
                <tbody>
                <tr>
                    <td align="center" bgcolor="#3e6ae1"
                        style="min-width: 120px; height: 36px; border: 2px solid rgb(62, 106, 225); cursor: auto; background: rgb(62, 106, 225); border-radius: 4px; font-size: 14px; font-weight: 700; font-family: Arial; line-height: 12.8px"
                        valign="middle" height="36"><a
                            href="<?= $cta_href ?>"
                            style=" background: rgb(62, 106, 225); color: rgb(255,
                            255, 255); margin: 0; text-decoration: none; text-transform: none;
                            padding: 10px 25px; display: block; border-radius: 4px; font-size: 14px;
                            font-weight: 700; font-family: Arial; line-height: 12.8px"
                            target="_blank"> <?= $cta_label ?> </a></td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
<?php } ?>
<tr>
    <td align="left"
        style="font-size: 0px; padding: 56px 56px 0px">
        <div
            style="text-align: left; color: rgb(0, 0, 0); font-family: Arial; font-size: 14px; line-height: 21px">
            <table width="100%"
                   style="border-top: 1px solid rgb(92, 94, 98); padding: 8px 0 0">
                <tbody>
                <tr
                    style="color: rgb(92, 94, 98); font-weight: 700; line-height: 22px; font-family: &quot;Arial&quot;; font-size: 16px">
                    <td
                        style="padding: 0; padding-top: 15px"> O-Replay
                    </td>
                </tr>
                <tr>
                    <td
                        style="font-size: 12px; line-height: 18px; font-family: &quot;Arial&quot;; color: rgb(92, 94, 98); padding: 16px 0 0">
                        <table>
                            <tbody>
                            <tr>
                                <td style="padding: 0">
                                    <a
                                        href="https://www.oreplay.es/About-us"
                                        style="color: rgb(92, 94, 98); text-decoration: none"
                                        target="_blank">About us</a> |
                                    <a
                                        href="https://www.oreplay.es/legal-notice"
                                        style="color: rgb(92, 94, 98); text-decoration: none"
                                        target="_blank">Legal</a> | <a
                                        href="https://www.oreplay.es/privacy-policy"
                                        style="color: rgb(92, 94, 98); text-decoration: none"
                                        target="_blank">Privacy</a></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </td>
</tr>
</tbody>
