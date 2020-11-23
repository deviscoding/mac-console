<?php

namespace DevCoding\Mac\Command;

use DevCoding\Command\Base\AbstractConsole;
use DevCoding\Mac\Objects\MacDevice;
use DevCoding\Mac\Objects\MacOs;
use DevCoding\Mac\Objects\MacUser;
use DevCoding\Mac\Utility\MacShellTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AbstractMacConsole.
 *
 * @package DevCoding\Mac\Command
 */
abstract class AbstractMacConsole extends AbstractConsole
{
  use MacShellTrait;

  const OPTION_USER = 'user';

  /** @var string[] */
  protected $_binary;
  /** @var MacDevice */
  protected $_MacDevice;
  /** @var MacUser */
  protected $_MacUser;
  /** @var string */
  protected $_user;

  /**
   * @return bool
   */
  abstract protected function isAllowUserOption();

  protected function configure()
  {
    if ($this->isAllowUserOption())
    {
      $this->addOption(self::OPTION_USER, null, InputOption::VALUE_REQUIRED, 'The user for which to to run the command.');
    }

    parent::configure();
  }

  // region //////////////////////////////////////////////// Software Methods

  /**
   * @param string $bin
   *
   * @return string
   *
   * @throws \Exception
   */
  protected function getBinaryPath($bin)
  {
    if (empty($this->_binary[$bin]))
    {
      $this->_binary[$bin] = parent::getBinaryPath($bin);
    }

    return $this->_binary[$bin];
  }

  /**
   * @return MacDevice
   */
  protected function getDevice()
  {
    if (empty($this->_MacDevice))
    {
      if ($this->isMac())
      {
        $this->_MacDevice = new MacDevice();
      }
    }

    return $this->_MacDevice;
  }

  /**
   * @return MacOs
   */
  protected function getOs()
  {
    return $this->getDevice()->getOs();
  }

  /**
   * @return MacUser|null
   *
   * @throws \Exception
   */
  protected function getUser()
  {
    if (empty($this->_MacUser))
    {
      if (!empty($this->_user))
      {
        $this->_MacUser = MacUser::fromString($this->_user);
      }
      else
      {
        $this->_MacUser = MacUser::fromConsole();
      }
    }

    return $this->_MacUser;
  }

  // region //////////////////////////////////////////////// Convenience Methods

  /**
   * @return bool
   */
  protected function isBatteryPowered()
  {
    return $this->getDevice()->isBatteryPowered();
  }

  /**
   * Determines if the OS version is Catalina or greater.
   *
   * @return bool
   */
  protected function isCatalinaUp()
  {
    if ($v = $this->getOs()->getVersion())
    {
      if (11 == $v->getMajor() || 10 == $v->getMajor() && $v->getMinor() >= 15)
      {
        return true;
      }
    }

    return false;
  }

  /**
   * @return bool
   */
  protected function isDisplaySleepPrevented()
  {
    return $this->getDevice()->isDisplaySleepPrevented();
  }

  /**
   * Determines if the OS is currently encrypting a FileVault.
   *
   * @return bool
   */
  protected function isEncryptingFileVault()
  {
    return $this->getOs()->isEncryptingFileVault();
  }

  /**
   * @return bool
   */
  protected function isSecurityChip()
  {
    return $this->getDevice()->isSecureBoot();
  }

  /**
   * @return bool
   */
  protected function isSecureBoot()
  {
    return $this->getDevice()->isSecureBoot();
  }

  // endregion ///////////////////////////////////////////// End Convenience Methods

  /**
   * Returns an opinionated determination of whether the CPU load is 'high' based on the current load and the number of
   * CPU cores. Loads greater than half the number of CPU cores are considered high.  This intentinoally conservative.
   *
   * @return bool
   */
  protected function isLoadHigh()
  {
    if (function_exists('sys_getloadavg'))
    {
      $cores = $this->getDevice()->getCpuCores() / 2;
      $load  = sys_getloadavg();

      return isset($load[0]) && (float) $load[0] > $cores;
    }

    return false;
  }

  /**
   * Determines if running on a Mac.
   *
   * @return bool
   */
  public function isMac()
  {
    return  PHP_OS === 'Darwin';
  }

  /**
   * @param string $src
   *
   * @throws \Exception
   */
  protected function rrmdir($src)
  {
    if (is_dir($src))
    {
      $uDir   = $this->getUser()->getDir();
      $inTmp  = (0 === strpos($src, '/tmp') && '/tmp' !== $src);
      $inUser = (0 === strpos($src, $uDir) && $src !== $uDir);

      if (!$inTmp && !$inUser)
      {
        throw new \Exception(sprintf('The directory "%s" cannot be deleted with this application.', $src));
      }

      $dir = opendir($src);
      while (false !== ($file = readdir($dir)))
      {
        if (('.' != $file) && ('..' != $file))
        {
          $full = $src.'/'.$file;
          if (is_dir($full))
          {
            $this->rrmdir($full);
          }
          else
          {
            unlink($full);
          }
        }
      }
      closedir($dir);
      rmdir($src);
    }
  }
}
