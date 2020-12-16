<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\MacShellTrait;

class MacNetworkSubsystem
{
  use MacShellTrait;

  const NETWORKSETUP = '/usr/sbin/networksetup -listallhardwareports';

  /** @var string[] */
  protected $_all;
  /** @var string[] */
  protected $_wifi;
  /** @var string[] */
  protected $_pan;
  /** @var string[] */
  protected $_wired;
  /** @var string[] */
  protected $_vpn;

  // region //////////////////////////////////////////////// Public Methods

  /**
   * Returns an array of Bluetooth PAN interface ports.
   *
   * @return string[]
   */
  public function getBluetoothInterfaces()
  {
    if (is_null($this->_pan))
    {
      $this->_pan = explode("\n", $this->getShellExec(self::NETWORKSETUP." | grep -A2 'Bluetooth' | grep -o en."));
    }

    return $this->_pan;
  }

  /**
   * Returns an array of all network interface ports.
   *
   * @return string[]
   */
  public function getNetworkInterfaces()
  {
    if (is_null($this->_all))
    {
      $this->_all = explode("\n", $this->getShellExec(self::NETWORKSETUP." | grep 'en' | grep -o en."));
    }

    return $this->_all;
  }

  public function getVpnInterfaces()
  {
    if (is_null($this->_vpn))
    {
      $this->_vpn = [];
      if ($i = $this->getShellExec(("/sbin/ifconfig -u | /usr/bin/grep 'POINTOPOINT'")))
      {
        $lines = explode("\n", $i);
        foreach ($lines as $line)
        {
          if (false !== strpos($line, 'POINTOPOINT'))
          {
            if (preg_match('#^([^:]*):.*#', $line, $matches))
            {
              $this->_vpn[] = $matches[1];
            }
          }
        }
      }
    }

    return $this->_vpn;
  }

  public function getVpnIp()
  {
    if ($interfaces = $this->getVpnInterfaces())
    {
      foreach ($interfaces as $interface)
      {
        if ($ip = $this->getIpV4($interface))
        {
          return $ip;
        }
      }
    }

    return null;
  }

  /**
   * Returns an array of Wi-Fi network interface ports.
   *
   * @return string[]
   */
  public function getWiFiInterfaces()
  {
    if (is_null($this->_wifi))
    {
      $this->_wifi = explode("\n", $this->getShellExec(self::NETWORKSETUP." | grep -A2 'Wi-Fi\|Airport' | grep -o en."));
    }

    return $this->_wifi;
  }

  /**
   * Returns an array of ethernet network interface ports.
   *
   * @return string[]
   */
  public function getWiredInterfaces()
  {
    if (empty($this->_wired))
    {
      $this->_wired = array_diff($this->getNetworkInterfaces(), $this->getWiFiInterfaces(), $this->getBluetoothInterfaces());
    }

    return $this->_wired;
  }

  /**
   * @return bool
   */
  public function isActiveEthernet()
  {
    if ($interfaces = $this->getWiredInterfaces())
    {
      foreach ($interfaces as $interface)
      {
        if ($this->isInterfaceActive($interface))
        {
          return true;
        }
      }
    }

    return false;
  }

  public function isActiveVpn()
  {
    if ($interfaces = $this->getVpnInterfaces())
    {
      foreach ($interfaces as $interface)
      {
        if ($this->isInterfaceActive($interface))
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * @return bool
   */
  public function isActiveWifi()
  {
    if ($interfaces = $this->getWiFiInterfaces())
    {
      foreach ($interfaces as $interface)
      {
        if ($this->isInterfaceActive($interface))
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Returns TRUE if the device has Wi-Fi hardware.
   *
   * @return bool
   */
  public function isWifi()
  {
    return !empty($this->getWiFiInterfaces());
  }

  /**
   * Returns TRUE if the device has Ethernet hardware.
   *
   * @return bool
   */
  public function isWired()
  {
    return !empty($this->getWiredInterfaces());
  }

  // region //////////////////////////////////////////////// Helper Functions

  protected function getIpV4($interface)
  {
    if ($retval = $this->getShellExec(sprintf('/sbin/ifconfig %s 2>/dev/null', $interface)))
    {
      $lines = explode("\n", $retval);
      foreach ($lines as $line)
      {
        if (preg_match('#^\s*inet\s([0-9.]*).*#', $line, $matches))
        {
          return $matches[1];
        }
      }
    }

    return null;
  }

  /**
   * Tests the given interface to determine if it is active.
   *
   * @param string $interface
   *
   * @return bool
   */
  protected function isInterfaceActive($interface)
  {
    if ($ip = $this->getIpV4($interface))
    {
      if (false === strpos($ip, '127.0.' && false === strpos($ip, '169.254.')))
      {
        return true;
      }
    }

    return false;
  }

  // endregion ///////////////////////////////////////////// End Helper Functions
}
