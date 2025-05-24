<?php

namespace Comba\Bundle\Modx;

use Comba\Core\Entity;
use DocumentParser;
use function Comba\Functions\safeHTML;
use function Comba\Functions\sanitizeID;

class ModxUser extends ModxOptions
{

    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        parent::__construct($parent, $modx);
        $this->setOptions('GetSessionName', Entity::get('SESSION_NAME') . strtolower(get_class($this)));
        $this->prepareUserEnv();
    }

    public function prepareUserEnv(array $user = null): self
    {
        if ($this->isBot()) {
            $this->setOptions([
                'id' => false,
                'name' => 'isBot',
                'type' => ''
            ]);
            return $this;
        }

        $session_id = null;
        $modx = $this->getModx();

        // Try to get MODX user
        if (is_object($modx)) {
            // modx web user
            $user_id = $modx->getLoginUserID('web');
            if ($user_id) {
                $user = $modx->getWebUserInfo($user_id);
                $user_id = 'web' . $user_id;
            }

            // modx manager user
            if (empty($user) && !empty($_SESSION['mgrInternalKey'])) {
                $user_id = $_SESSION['mgrInternalKey'];
                $user = $modx->getUserInfo($user_id);
                $user['fullname'] = $user['fullname'] ?? $user['username'];
                $user_id = 'man' . $user_id;
            }

            if (!empty($user_id)) {
                $session_id = $this->createSessionID($user_id);
            }
        }

        // non-MODX user
        if (empty($user)) {
            $session_name = $this->getOptions('GetSessionName');
            $session_id = $_SESSION[$session_name] ?? $_COOKIE[$session_name] ?? null;

            $session_id = sanitizeID($session_id);
            $session_id = $session_id ? str_rot13($session_id) : null;

            if (empty($session_id)) {
                $session_id = $this->createSessionID();
                $transformuserid = str_rot13($session_id);

                $this->setSecureCookie($session_name, $transformuserid);
                $_SESSION[$session_name] = $transformuserid;
            } else {
                $session_id = str_rot13($session_id);
            }

            $user = [
                'internalKey' => '-1',
                'fullname' => 'NaUser'
            ];
        } else {
            if (!empty($user['email'])) {
                $this->setOptions('email', $user['email']);
            }
        }

        $this->setSession($session_id);
        $this->setOptions([
            'id' => safeHTML($user['internalKey']),
            'name' => safeHTML($user['fullname']),
            'type' => $user['usertype'] ?? ''
        ]);

        return $this;
    }

    public function setSecureCookie(string $name, string $value, ?int $lifetime = null): bool
    {
        $lifetime = $lifetime ?? strtotime('+3 month');
        $options = [
            'expires' => time() + $lifetime,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        return \setcookie($name, $value, $options);
    }

    /** Створює ідентифікатор сесії користувача
     * @param string|null $modxuserid
     * @return string
     */
    private function createSessionID(?string $modxuserid = null): string
    {
        return !empty($modxuserid) ? $this->guidv4($modxuserid, Entity::getProtected('USER_SALT')) : $this->createUniqueCode();
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

    public function getType():?string
    {
        return $this->getOptions('type');
    }

    public function getEmail(): ?string
    {
        return $this->getOptions('email');
    }
}
