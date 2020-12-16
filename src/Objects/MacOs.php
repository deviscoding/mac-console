<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\MacShellTrait;

class MacOs
{
  use MacShellTrait;

  /** @var MacOsVersion */
  protected $_darwin;
  /** @var MacUser */
  protected $_user;

  public function __clone()
  {
    $this->_user = null;
  }

  /**
   * @return MacOsVersion|null
   */
  public function getVersion()
  {
    if (empty($this->_darwin))
    {
      if ($v = $this->getShellExec('/usr/bin/sw_vers -productVersion'))
      {
        $this->_darwin = new MacOsVersion($v);
      }
    }

    return $this->_darwin;
  }

  /**
   * Determines if the OS is currently encrypting a FileVault.
   *
   * @return bool
   */
  public function isEncryptingFileVault()
  {
    $fv = is_file('/usr/bin/fdesetup') ? $this->getProcessOutput('/usr/bin/fdesetup status') : '';

    return false !== strpos($fv, 'Encryption in progress');
  }

  protected function withUser(MacUser $macUser)
  {
    $clone        = clone  $this;
    $clone->_user = $macUser;

    return $clone;
  }
}
