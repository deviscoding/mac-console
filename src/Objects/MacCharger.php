<?php

namespace DevCoding\Mac\Objects;

/**
 * Class MacCharger.
 *
 * @package DevCoding\Mac\Objects
 */
class MacCharger extends AbstractMacPower
{
  /**
   * @return string|null
   */
  public function getWattage()
  {
    return $this->isConnected() ? $this->getPowerDataType('Wattage (W)') : false;
  }

  /**
   * @return bool
   */
  public function isActive()
  {
    return 'Yes' == $this->getPowerDataType('Charging');
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    return 'Yes' == $this->getPowerDataType('Connected');
  }
}
