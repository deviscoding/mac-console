<?php

namespace DevCoding\Mac\Objects;

class MacBattery extends AbstractMacPower
{
  /** @var bool */
  protected $installed;
  /** @var bool */
  protected $condition;
  /** @var array */
  protected $cycles;
  /** @var string */
  protected $serial;

  /**
   * @return bool
   */
  public function isInstalled()
  {
    if (is_null($this->installed))
    {
      $this->installed = (false !== stripos($this->getPmsetBatt(), 'battery'));
    }

    return $this->installed;
  }

  /**
   * @return bool
   */
  public function isActive()
  {
    return false !== strpos($this->getPmsetPs(), 'Battery Power');
  }

  /**
   * @return bool
   */
  public function isCharging()
  {
    return false !== stripos($this->getPmsetBatt(), '; charging;');
  }

  public function isHealthy()
  {
    return !$this->isInstalled() || 'Normal' == $this->getCondition();
  }

  public function getCondition()
  {
    if (!isset($this->condition))
    {
      if ($this->isInstalled())
      {
        $this->condition = $this->getPowerDataType('Condition');
      }
      else
      {
        $this->condition = false;
      }
    }

    return $this->condition;
  }

  /**
   * @return int|null
   */
  public function getCycles()
  {
    if (!isset($this->cycles))
    {
      if ($this->isInstalled())
      {
        $this->cycles = $this->getPowerDataType('Cycle Count');
      }
      else
      {
        $this->cycles = false;
      }
    }

    return $this->cycles;
  }

  /**
   * @return string|bool|null
   */
  public function getPercentage()
  {
    if ($this->isInstalled())
    {
      if ($batt = $this->getPmsetBatt())
      {
        if (preg_match('/([0-9]+)%;/', $batt, $matches))
        {
          return $matches[1];
        }
      }

      return null;
    }

    return false;
  }

  /**
   * @return string|bool|null;
   */
  public function getSerialNumber()
  {
    if (!isset($this->serial))
    {
      if ($this->isInstalled())
      {
        $this->serial = $this->getPowerDataType('Serial Number');
      }
      else
      {
        $this->serial = false;
      }
    }

    return $this->serial;
  }

  /**
   * @param string $format
   *
   * @return \DateInterval|string|bool|null
   */
  public function getUntilEmpty($format = null)
  {
    if ($this->isInstalled())
    {
      if ($batt = $this->getPmsetBatt())
      {
        if ($Interval = $this->getRemaining($batt))
        {
          return ($format) ? $Interval->format($format) : $Interval;
        }
      }

      return null;
    }

    return false;
  }

  /**
   * @param string $format
   *
   * @return \DateInterval|string|bool|null
   */
  public function getUntilFull($format = null)
  {
    if ($this->isInstalled())
    {
      if ($batt = $this->getPmsetBatt())
      {
        if (false !== stripos($batt, '; charg'))
        {
          if ($Interval = $this->getRemaining($batt))
          {
            return ($format) ? $Interval->format($format) : $Interval;
          }
        }
      }

      return null;
    }

    return false;
  }

  /**
   * @param string $str
   *
   * @return \DateInterval|null
   */
  protected function getRemaining($str)
  {
    if (preg_match('#([0-9]+):([0-9]{1,2})\sremaining#', $str, $matches))
    {
      try
      {
        return new \DateInterval(sprintf('PT%sH%sM', $matches[1], $matches[2]));
      }
      catch (\Exception $e)
      {
        return null;
      }
    }

    return null;
  }
}
