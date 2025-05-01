<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;
use function Comba\Functions\safeHTML;
use function setcookie;

class ModxUser extends ModxOptions
{

    public function __construct($modx)
    {
        parent::__construct($modx);
        $this->setOptions('GetSessionName', Entity::SESSION_NAME . strtolower(get_class($this)));
        $this->prepareUserEnv();
    }

    public function prepareUserEnv(array $user = null)
    {
        $session_id = null;
        if ($this->isBot()) {
            $user['internalKey'] = false;
            $user['fullname'] = 'isBot';
        } else {

            // Get modx webuser
            if (is_object($this->getModx())) {
                $user_id = $this->getModx()->getLoginUserID('web');
                if (!empty($user_id)) {
                    $user = $this->getModx()->getWebUserInfo($user_id);
                }

                $session_id = $this->createSessionID($user_id);

                // Get modx manager
                if (empty($user)) {
                    $userid = $_SESSION['mgrInternalKey'];
                    if (!empty($userid)) {
                        $user = $this->getModx()->getUserInfo($userid);
                        $user['fullname'] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
                    }
                }
            }

            /*
             * non-modx user
             */
            if (empty($user)) {

                $user['internalKey'] = '-1';
                $user['fullname'] = 'NaUser';
                $_s = isset($_SESSION[$this->getOptions('GetSessionName')]) ? $_SESSION[$this->getOptions('GetSessionName')] : null;
                $_c = isset($_COOKIE[$this->getOptions('GetSessionName')]) ? $_COOKIE[$this->getOptions('GetSessionName')] : null;
                $_s = empty($_s) ? $_c : $_s;
                $session_id = $_s;
                if (empty($_s) && !empty($_c)) {
                    $session_id = safeHTML($_c);
                }

                if (empty($session_id)) {
                    $session_id = $this->createSessionID();
                    $transformuserid = str_rot13($session_id);//encode
                    if (!empty($transformuserid)) {
                        setcookie($this->getOptions('GetSessionName'), $transformuserid, strtotime('+1 month'), '/');
                        $_SESSION[$this->getOptions('GetSessionName')] = $transformuserid;
                    }
                } else {
                    $session_id = str_rot13($session_id);//decode
                }
            }
            $this->setSession($session_id);
        }
        $this->setOptions([
            'id' => safeHTML($user['internalKey']),
            'name' => safeHTML($user['fullname']),
            'type' => $user['usertype'] ?? ''
        ]);
    }

    private function createSessionID(int $modxuserid = null): string
    {
        if ($modxuserid !== null) {
            $site = preg_replace("/[^a-zA-Z0-9]+/", "", filter_var(Entity::getServerName(), FILTER_SANITIZE_URL));
            // sitename + modxuser id. stupid release.
            $session_id = substr(!empty($site) ? $site . $modxuserid : $modxuserid, -24);
        } else {
            $session_id = $this->createUniqueCode();
        }

        return $session_id;
    }

    public function setSession(string $userid): void
    {
        $this->setOptions($this->getOptions('GetSessionName'), $userid);
    }

    /** Get User ID
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getOptions('id');
    }

    public function getName(): ?string
    {
        return $this->getOptions('name');
    }

    public function getSession(): ?string
    {
        return $this->getOptions($this->getOptions('GetSessionName'));
    }
}
