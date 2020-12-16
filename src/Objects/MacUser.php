<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\MacShellTrait;

class MacUser
{
  use MacShellTrait;

  const TEMPLATE_UDIR    = '/Users/%s';
  const TEMPLATE_LIBRARY = '%s/Library';

  /** @var int */
  protected $_id;
  /** @var string */
  protected $_username;
  /** @var string */
  protected $_dir;
  /** @var string */
  protected $_library;

  /**
   * @param bool $throw
   *
   * @return MacUser|null
   *
   * @throws \Exception
   */
  public static function fromConsole($throw = true)
  {
    $MacUser = new MacUser();
    if ($cUser = $MacUser->getConsoleUser())
    {
      return $MacUser->setUsername($cUser)->setId($MacUser->getUserId($cUser));
    }
    elseif (!$throw)
    {
      return null;
    }

    throw new \Exception('Could not determine username of console user.');
  }

  public static function fromString($string, $throw = true)
  {
    $MacUser = new MacUser();
    if ($MacUser->isAlphaNumeric($string))
    {
      return $MacUser->setUsername($string);
    }
    elseif (!$throw)
    {
      return null;
    }

    throw new \Exception('Invalid Username Given; only Alphanumeric Characters Allowed');
  }

  public function __toString()
  {
    $name = $this->getUserName();

    return is_string($name) ? $name : 'Unknown';
  }

  /**
   * @return string
   */
  public function getDir()
  {
    if (empty($this->_dir))
    {
      $this->_dir = sprintf(self::TEMPLATE_UDIR, $this->getUserName());
    }

    return $this->_dir;
  }

  /**
   * @return int
   */
  public function getId()
  {
    if (empty($this->_id))
    {
      $this->_id = (int) $this->getUserId($this->getUserName());
    }

    return $this->_id;
  }

  /**
   * @return string
   */
  public function getLibrary()
  {
    if (empty($this->_library))
    {
      $this->_library = sprintf(self::TEMPLATE_LIBRARY, $this->getDir());
    }

    return $this->_library;
  }

  /**
   * @return string
   */
  public function getUserName()
  {
    return $this->_username;
  }

  /**
   * @param string $key The name of the password in the keychain
   *
   * @return string|null The retrieved password
   *
   * @throws \Exception If the security binary isn't present
   */
  public function getPasswordFromKeychain($key)
  {
    if ($security = $this->getBinaryPath('security'))
    {
      $cmd = sprintf('%s -q find-generic-password -a %s -s %s -w', $security, $this->getUserName(), $key);

      exec($cmd, $output, $retval);

      if (0 === $retval && !empty($output))
      {
        if (!empty($output[0]))
        {
          return $output[0];
        }
      }
    }

    return null;
  }

  /**
   * @param mixed $id
   *
   * @return MacUser
   */
  public function setId($id)
  {
    $this->_id = $id;

    return $this;
  }

  /**
   * Saves a password in the user's macOS keychain.
   *
   * @param string $key the name of the password in keychain
   * @param string $pw  the password itself
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function setPasswordInKeychain($key, $pw)
  {
    if ($security = $this->getBinaryPath('security'))
    {
      $cmd = sprintf('%s add-generic-password -a %s -s %s -w %s', $security, $this->getUserName(), $key, $pw);

      exec($cmd, $output, $retval);

      if (0 === $retval)
      {
        return $this;
      }
    }

    throw new \Exception('Could not save password in keychain.');
  }

  /**
   * @param mixed $username
   *
   * @return MacUser
   */
  public function setUsername($username)
  {
    $this->_username = $username;

    return $this;
  }
}
