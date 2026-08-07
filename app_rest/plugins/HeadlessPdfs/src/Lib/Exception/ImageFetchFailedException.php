<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Exception;

use RestApi\Lib\Exception\DetailedException;

class ImageFetchFailedException extends DetailedException
{
    protected int $_defaultCode = 502;
}
