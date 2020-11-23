<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\MacShellTrait;

class MacUser
{
  use MacShellTrait;

  const TEMPLATE_UDIR    = '/Users/%s';
  const TEMPLATE_LIBRARY = '%s/Libary';

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
      return $MacUser->setUsername($MacUser);
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
      $this->_id = (int) $this->getShellExec(sprintf('/usr/bin/id -u %s', $this->getUserName()));
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
