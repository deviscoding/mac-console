<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Command\Base\Traits\ShellTrait;
use PHLAK\SemVer\Version;

class MacApplication extends \SplFileInfo
{
  use ShellTrait;

  public function getCopyright()
  {
    return $this->getPlistValue('NSHumanReadableCopyright');
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->getPlistValue('CFBundleName');
  }

  /**
   * @return string|null
   */
  public function getIdentifier()
  {
    return $this->getPlistValue('CFBundleIdentifier');
  }

  /**
   * @return SemanticVersion|Version|null
   */
  public function getShortVersion()
  {
    return new Version($this->getPlistValue('CFBundleShortVersionString'));
  }

  /**
   * @return SemanticVersion|Version|null
   */
  public function getVersion()
  {
    return new Version($this->getPlistValue('CFBundleVersion'));
  }

  protected function getPlistValue($key)
  {
    return $this->getShellExec(sprintf('defaults read %s %s', $this->getInfoPath(), $key));
  }

  public function getInfoPath()
  {
    return $this->getPathname().'/Contents/Info.plist';
  }
}
