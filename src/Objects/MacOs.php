<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\MacShellTrait;

class MacOs
{
  use MacShellTrait;

  /** @var MacOsVersion */
  protected $_darwin;

  /**
   * @return MacOsVersion|null
   */
  public function getVersion()
  {
    if (empty($this->_darwin))
    {
      if ($v = $this->getShellExec('sw_vers -productVersion'))
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
    $fv = $this->getProcessOutput('/usr/bin/fdesetup status');

    return false !== strpos($fv, 'Encryption in progress');
  }
}
