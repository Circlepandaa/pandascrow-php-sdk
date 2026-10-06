<?php
namespace Pandascrow;

class PandascrowException extends \RuntimeException
{
    /** @var int */ public $httpStatus;
    /** @var string|null */ public $docUrl;
    /** @var array|null */ public $response;

    public function __construct(string $message, int $httpStatus = 0, ?string $docUrl = null, ?array $response = null)
    {
        parent::__construct($message, $httpStatus);
        $this->httpStatus = $httpStatus;
        $this->docUrl = $docUrl;
        $this->response = $response;
    }
}
