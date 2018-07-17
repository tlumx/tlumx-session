<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/tlumx-servicecontainer
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/tlumx-servicecontainer/blob/master/LICENSE  (MIT License)
 */
namespace Tlumx\Session;

/**
 * Session class.
 */
class Session
{
    /**
     * @var string
     */
    protected $tlumxKey = '__tlumx';

    /**
     * @var \SessionHandlerInterface
     */
    protected $saveHandler;

    /**
     * @var array
     */
    protected $sessionOptions = [
        'save_path',
        'name',
        'save_handler',
        'gc_probability',
        'gc_divisor',
        'gc_maxlifetime',
        'serialize_handler',
        'cookie_lifetime',
        'cookie_path',
        'cookie_domain',
        'cookie_secure',
        'cookie_httponly',
        'use_cookies',
        'use_only_cookies',
        'referer_check',
        'entropy_file',
        'entropy_length',
        'cache_limiter',
        'cache_expire',
        'use_trans_sid',
        'hash_function',
        'hash_bits_per_character',
        'url_rewriter.tags',
        'upload_progress.enabled',
        'upload_progress.cleanup',
        'upload_progress.prefix',
        'upload_progress.name',
        'upload_progress.freq',
        'upload_progress.min_freq'
    ];

    /**
     * Construct
     *
     * @param array $options
     * @param \SessionHandlerInterface $saveHandler
     */
    public function __construct(array $options = [], \SessionHandlerInterface $saveHandler = null)
    {
        session_cache_limiter('');
        ini_set('session.use_cookies', 1);
        ini_set('session.cookie_lifetime', 0);
        $this->setOptions($options);
        if ($saveHandler !== null) {
            $this->setSaveHandler($saveHandler);
        }
        session_register_shutdown();
    }

    /**
     * Get session options
     *
     * @param string $option
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getOptions($option = null)
    {
        $options = [];
        foreach (ini_get_all('session') as $name => $value) {
            $options[substr($name, 8)] = $value['local_value'];
        }

        if ($option) {
            if (array_key_exists($option, $options)) {
                return $options[$option];
            } else {
                throw new \InvalidArgumentException('Unknown option "$option".');
            }
        }

        return $options;
    }

    /**
     * Set session options
     *
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (in_array($key, $this->sessionOptions)) {
                ini_set('session.' . $key, $value);
            } else {
                throw new \InvalidArgumentException('Unknown option "$key".');
            }
        }
    }

    /**
     * Get session savehandler
     *
     * @return \SessionHandlerInterface
     */
    public function getSaveHandler()
    {
        return $this->saveHandler;
    }

    /**
     * Set SessionHandler
     *
     * @param \SessionHandlerInterface $saveHandler
     */
    public function setSaveHandler(\SessionHandlerInterface $saveHandler)
    {
        $this->saveHandler = $saveHandler;
        session_set_save_handler($this->saveHandler, true);
    }

    /**
     * Is session stated
     *
     * @return bool
     */
    public function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE ? true : false;
    }

    /**
     * Start session
     *
     * @throws \RuntimeException
     */
    public function start()
    {
        if ($this->isStarted()) {
            return;
        }

        if (session_start() === false) {
            throw new \RuntimeException('Could not start the session');
        }
    }

    /**
     * Ends the current session and store session data
     */
    public function close()
    {
        if ($this->isStarted()) {
            session_write_close();
        }
    }

    /**
     * Frees all session variables and destroys all data registered to a session
     */
    public function destroy()
    {
        if (!$this->isStarted()) {
            return;
        }

        session_unset();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Get session ID
     *
     * @return mixed
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Set session ID
     *
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setId($value)
    {
        if ($this->isStarted()) {
            throw new \InvalidArgumentException('Set session id must be before start session.');
        }

        session_id($value);
    }

    /**
     * Regenerate session ID
     *
     * @param bool $deleteOldSession
     * @return bool
     */
    public function regenerateID($deleteOldSession = true)
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Get session name
     *
     * @return string
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Attempt to set the session name
     *
     * @param string $value
     * @throws \InvalidArgumentException
     */
    public function setName($value)
    {
        if ($this->isStarted()) {
            throw new \InvalidArgumentException('Set session name must be before start session.');
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            throw new \InvalidArgumentException('Name should contain only alphanumeric characters.');
        }

        session_name($value);
    }

    /**
     * Set session cookie lifetime
     *
     * @param int $lifetime
     */
    protected function setCookieLifetime($lifetime)
    {
        ini_set('session.cookie_lifetime', (int) $lifetime);

        if ($this->isStarted()) {
            $this->regenerateID();
        }
    }

    /**
     * Set the TTL (in seconds) for the session cookie expiry
     *
     * @param int $lifetime
     * @throws \InvalidArgumentException
     */
    public function rememberMe($lifetime = 1209600)
    {
        if ($this->isStarted()) {
            throw new \RuntimeException(
                'Set session rememberMe (the session cookie expiry) must be before start session.'
            );
        }

        if (!is_numeric($lifetime)) {
            throw new \InvalidArgumentException('Parameter "lifetime" must be numeric.');
        }

        if (0 > $lifetime) {
            throw new \InvalidArgumentException('Parameter "lifetime" must be a positive integer or zero');
        }

        $this->setCookieLifetime($lifetime);
    }

    /**
     * Set a 0s TTL for the session cookie
     */
    public function forgetMe()
    {
        $this->setCookieLifetime(0);
    }

    /**
     * Returns the session variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Adds a session variable
     *
     * @param string $key
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function set($key, $value)
    {
        $this->start();

        if (!is_string($key) || empty($key)) {
            throw new \InvalidArgumentException('Key must be a non-empty string.');
        }

        if ($key[0] == "_") {
            throw new \InvalidArgumentException('Key cannot start session with underscore.');
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Is isset session variable
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    /**
     * Get all session variable
     *
     * @return array
     */
    public function getAll()
    {
        $this->start();
        $all  = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $this->tlumxKey, 0) === false) {
                $all[$key] = $value;
            }
        }
        return $all;
    }

    /**
     * Removes session variable
     *
     * @param string $key
     * @return mixed
     */
    public function remove($key)
    {
        $this->start();
        if (!isset($_SESSION[$key])) {
            return;
        }

        unset($_SESSION[$key]);
    }

    /**
     * Removes all session variables
     */
    public function removeAll()
    {
        $this->start();
        $_SESSION = [];
    }

    /**
     * Gets ot sets flash messages.
     * If the value parameter is passed the message is set, otherwise it is retrieved.
     * After the message is retrieved for the first time it is removed.
     *
     * @param string $key  The name of the flash message
     * @param mixed $value  Flash message content
     * @return mixed
     */
    public function flash($key, $value = null)
    {
        if (!is_string($key) || empty($key)) {
            throw new \InvalidArgumentException('Key must be a non-empty string.');
        }

        $this->start();

        $key = $this->tlumxKey . '.' . $key;
        if ($value != null) {
            $_SESSION[$key] = $value;
        } elseif (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        return $value;
    }
}
