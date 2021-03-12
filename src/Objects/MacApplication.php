<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Command\Base\Traits\ShellTrait;
use PHLAK\SemVer\Version;

class MacApplication extends \SplFileInfo
{
  use ShellTrait;

  /**
   * @return bool
   */
  public function isUserApp()
  {
    return preg_match('#/Users/([^/]+)/Applications#', $this->getPath());
  }

  /**
   * @return bool
   */
  public function isSystemApp()
  {
    return 0 === strpos($this->getPath(), '/System') || 0 === strpos($this->getPath(), '/Library');
  }

  /**
   * @return string|null
   */
  public function getCopyright()
  {
    return str_replace('\251', '©', $this->getPlistValue('NSHumanReadableCopyright'));
  }

  /**
   * @return string
   */
  public function getName()
  {
    return ($name = $this->getPlistValue('CFBundleName')) ? $name : $this->getBasename('.app');
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
    try
    {
      return new SemanticVersion($this->getPlistValue('CFBundleShortVersionString'));
    }
    catch (\Exception $e)
    {
      return null;
    }
  }

  /**
   * @return SemanticVersion|Version|null
   */
  public function getVersion()
  {
    try
    {
      return new SemanticVersion($this->getPlistValue('CFBundleVersion'));
    }
    catch (\Exception $e)
    {
      return null;
    }
  }

  protected function getPlistValue($key)
  {
    return $this->getShellExec(sprintf('defaults read "%s" %s 2>/dev/null', $this->getInfoPath(), $key));
  }

  public function getInfoPath()
  {
    return $this->getPathname().'/Contents/Info.plist';
  }
}
