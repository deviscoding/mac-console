<?php

namespace DevCoding\Mac\Command;

use DevCoding\Command\Base\AbstractConsole;
use DevCoding\Mac\Objects\MacDevice;
use DevCoding\Mac\Objects\MacOs;
use DevCoding\Mac\Objects\MacUser;
use DevCoding\Mac\Utility\MacShellTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractMacConsole.
 *
 * @package DevCoding\Mac\Command
 */
abstract class AbstractMacConsole extends AbstractConsole
{
  use MacShellTrait;

  const OPTION_USER = 'user';

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

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    if ($this->isAllowUserOption() && $input->hasOption(self::OPTION_USER))
    {
      if ($user = $input->getOption(self::OPTION_USER))
      {
        $this->_user = $user;
      }
    }

    parent::interact($input, $output);
  }

  // region //////////////////////////////////////////////// Software Methods

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

  protected function openAsConsoleUser($toOpen)
  {
    $launchctl = $this->getBinaryPath('launchctl');
    $open      = $this->getBinaryPath('open');
    $id        = $this->getLaunchUserId();
    $method    = $this->getLaunchMethod();
    $command   = sprintf('%s %s %s %s "%s" > /dev/null 2>&1 &', $launchctl, $method, $id, $open, $toOpen);

    exec($command);
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
   * Determines if the OS version is Big Sur or greater.
   *
   * @return bool
   */
  protected function isBigSurUp()
  {
    if ($v = $this->getOs()->getVersion())
    {
      // In some early testing, the version returned was 10.18 or something silly
      if (11 == $v->getMajor() || 10 == $v->getMajor() && $v->getMinor() >= 16)
      {
        return true;
      }
    }

    return false;
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
   * Determines if the OS version is Mojave or greater.
   *
   * @return bool
   */
  protected function isMojaveUp()
  {
    if ($v = $this->getOs()->getVersion())
    {
      if (11 == $v->getMajor() || 10 == $v->getMajor() && $v->getMinor() >= 14)
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
    return PHP_OS === 'Darwin';
  }

  protected function getLaunchUserId()
  {
    if ('bsexec' === $this->getLaunchMethod())
    {
      return $this->getShellExec(sprintf('/usr/bin/pgrep -x -u "%s" loginwindow', $this->getUser()->getId()));
    }
    else
    {
      return $this->getUser()->getId();
    }
  }

  protected function getLaunchMethod()
  {
    return $this->getOs()->getVersion()->getMinor() <= 9 ? 'bsexec' : 'asuser';
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

  protected function setUserAsOwner($file, MacUser $MacUser, $recursive = false)
  {
    $uDir = $MacUser->getDir();
    if (false === strpos($file, $uDir))
    {
      throw new \Exception('You cannot set user ownership of a file that is not below the user directory.');
    }
    else
    {
      $owner = posix_getpwuid(fileowner($uDir));
      $group = posix_getgrgid(filegroup($uDir));

      if (!empty($owner['name']) && !empty($group['name']))
      {
        chown($file, $owner['name']);
        chgrp($file, $group['name']);

        if ($recursive)
        {
          $dir = dirname($file);
          while ($dir != $MacUser->getDir() && $dir != $MacUser->getLibrary())
          {
            if ($dir === dirname($dir))
            {
              throw new \Exception('You cannot recursively set user ownership of a file that is not below the user directory.');
            }

            chown($file, $owner['name']);
            chgrp($file, $group['name']);

            $dir = dirname($dir);
          }
        }
      }
    }

    return $this;
  }
}
