<?php

namespace Comba\Bundle\Modx\Tracking\Types;
/**
 * Basic class of Tracking
 */
class TrackingNone
{
    protected array $data = array();

    protected string $title;
    protected string $url;
    protected string $urltracking;

    private $_lasterror;
    private string $_lasterror_msg;

    /**
     * Return info of tracking
     *
     * @param string $declaration number
     * @param string $seller
     * @return string|null
     */
    public function getBarcodeInfo(string $declaration, string $seller): ?string
    {
        if (empty($declaration) || strlen($declaration) < 4) {
            return null;
        }
        return 'Наразі статус відправлення №' . $declaration . ' можливо <a href="' . $this->getUrlTracking() . $declaration . '" target=_blank>відстежити</a> тільки на сайті <a href="' . $this->getUrl() . '" target=_blank>' . $this->getTitle() . '</a>';
    }

    /**
     * Return tracking url
     *
     * @return string
     */
    public function getUrlTracking(): string
    {
        return $this->urltracking;
    }

    /**
     * Return site url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Return title of delivery ogranization
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Return last error info
     *
     * @param bool $returnMsg retun string insted _lassterror
     *
     * @return mixed
     */
    public function getLastError(bool $returnMsg = false)
    {
        return !empty($returnMsg) ? $this->_lasterror_msg : $this->_lasterror;
    }

    /**
     * Set last error info
     *
     * @param mixed $err error code
     * @param string $msg string
     *
     * @return void
     */
    public function setLastError($err, string $msg)
    {
        $this->_lasterror = $err;
        $this->_lasterror_msg = $msg;
    }

    /**
     * Return empty message by default
     *
     * @return string
     */
    public function getMessageEmpty(): string
    {
        return '';
    }

    /**
     * Bind type
     *
     * @return array
     */
    public function getSupportType(): array
    {
        return array('dt_none');
    }

    public function getType(): array
    {
        $act = array();
        foreach ($this->getSupportType() as $action) {
            $act = array_merge(array($action => get_class($this)), $act);
        }

        return $act;
    }
}
