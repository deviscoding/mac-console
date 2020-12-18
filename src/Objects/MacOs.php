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
   * @param string $app
   *
   * @return MacApplication
   */
  public function getApplication($app)
  {
    if ($apps = $this->getApplications())
    {
      foreach ($apps as $MacApplication)
      {
        if ($MacApplication->getFilename() == $app)
        {
          return $MacApplication;
        }
        elseif ($MacApplication->getBasename('.app') == $app)
        {
          return $MacApplication;
        }
        elseif ($MacApplication->getIdentifier() == $app)
        {
          return $MacApplication;
        }
        elseif ($MacApplication->getPathname() == $app)
        {
          return $MacApplication;
        }
      }
    }

    return null;
  }

  /**
   * @return MacApplication[]
   */
  public function getApplications()
  {
    if ($list = $this->getShellExec('mdfind "kMDItemKind == \'Application\'"'))
    {
      $apps  = [];
      $lines = explode("\n", $list);

      foreach ($lines as $line)
      {
        $apps[] = new MacApplication($line);
      }

      return $apps;
    }

    return null;
  }

  /**
   * @return string|null
   */
  public function getSoftwareUpdateCatalogUrl()
  {
    $cmd = '/usr/bin/defaults read "/Library/Managed Preferences/com.apple.SoftwareUpdate" CatalogURL 2>"/dev/null"';
    $url = $this->getShellExec($cmd);

    return (!empty($url) && 'None' != $url) ? $url : null;
  }

  /**
   * @return MacUser
   */
  public function getUser()
  {
    return $this->_user;
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
