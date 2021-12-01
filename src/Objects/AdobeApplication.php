<?php

namespace DevCoding\Mac\Objects;

class AdobeApplication extends MacApplication
{
  const PATH_TEMPLATES = [
      '/Applications/Adobe {name} {year}/Adobe {name} {year}.app/Contents/Info.plist',
      '/Applications/Adobe {name} {year}/Adobe {name}.app/Contents/Info.plist',
      '/Applications/Adobe {name}/Adobe {name}.app/Contents/Info.plist',
      '/Applications/Adobe {name} CC/Adobe {name}.app/Contents/Info.plist',
  ];

  const UNINSTALL = '/Library/Application\ Support/Adobe/Adobe\ Desktop\ Common/HDBox/Setup --uninstall=1 --sapCode={sap} --baseVersion={version} --deleteUserPreferences=false --platform=osx10-64';

  /** @var array[] */
  protected $_appInfo;
  /** @var string */
  protected $_sap;
  /** @var SemanticVersion */
  protected $_baseVersion;
  /** @var int */
  protected $_year;
  /** @var bool */
  protected $_cc;
  /** @var string */
  protected $_slug;

  /**
   * @param string   $slug
   * @param int|null $year
   *
   * @return AdobeApplication|null
   */
  public static function fromSlug($slug, $year = null)
  {
    // Check Slug for Year
    if (empty($year) && preg_match('#([a-zA-Z-]+)-([0-9]+)#', $slug, $matches))
    {
      $year = $matches[2];
      $slug = $matches[1];
    }

    if ($info = self::getAppInfoFromAdbArg($slug))
    {
      // Turn the provided year into an array, or get from baseVersions, including only numeric.
      if (!empty($year) || empty($info['baseVersions']))
      {
        $years = [$year];
      }
      else
      {
        $years = array_filter(
            array_values($info['baseVersions']),
            function ($v) {
              return is_numeric($v);
            }
        );
      }

      foreach ($info['names'] as $name)
      {
        foreach (self::PATH_TEMPLATES as $tmpl)
        {
          foreach ($years as $tYear)
          {
            $file = str_replace(['{name}', '{year}'], [$name, $tYear], $tmpl);

            if (file_exists($file))
            {
              return new AdobeApplication(dirname($file, 2));
            }
          }
        }
      }
    }

    return null;
  }

  /**
   * @return false|SemanticVersion
   */
  public function getBaseVersion()
  {
    if (is_null($this->_baseVersion))
    {
      $info = $this->getAdobeAppInfo($this->getSlug());
      $year = $this->getYear();

      foreach ($info['baseVersions'] as $baseVersion => $bYear)
      {
        if ($bYear == $year)
        {
          $ver = $baseVersion;
        }
      }

      $this->_baseVersion = isset($ver) ? new SemanticVersion($ver) : false;
    }

    return $this->_baseVersion;
  }

  /**
   * Returns the SAP code for the application, as defined by Adobe and taken from cc.json.
   *
   * @return string
   */
  public function getSap()
  {
    if (is_null($this->_sap))
    {
      $info = $this->getAdobeAppInfo($this->getSlug());

      $this->_sap = $info['sap'] ?? false;
    }

    return $this->_sap;
  }

  public function getSlug()
  {
    return $this->setFromPathName()->_slug;
  }

  /**
   * Returns a string for uninstalling the application from a Mac.
   *
   * @return string
   */
  public function getUninstall()
  {
    return str_replace(['{sap}', '{version}'], [$this->getSap(), $this->getBaseVersion()->getRaw()], self::UNINSTALL);
  }

  public function getYear()
  {
    return $this->setFromPathName()->_year;
  }

  public function isCC()
  {
    return $this->setFromPathName()->_cc;
  }

  // region //////////////////////////////////////////////// Helper Functions

  protected function setFromPathName()
  {
    if (!isset($this->_cc, $this->_year, $this->_slug))
    {
      if (preg_match('/\/Adobe\s(?<name>[a-zA-Z\s]+)\s?(?<year>[0-9]{4})?\//', $this->getPathname(), $matches))
      {
        $this->_cc   = (false !== strpos($matches['name'], 'CC'));
        $this->_year = $matches['year'];
        $this->_slug = $this->normalizeKey(trim(str_replace(' CC', '', $matches['name'])));
      }
    }

    return $this;
  }

  protected function getAdobeAppInfo($name)
  {
    if (empty($this->_appInfo[$name]))
    {
      $this->_appInfo[$name] = $this->getAppInfoFromAdbArg($name);
    }

    return $this->_appInfo[$name];
  }

  private static function getAppInfoFromAdbArg($str)
  {
    $dir   = '/Library/Application Support/Adobe/Uninstall';
    $years = ['2015', '2017', '2018', '2018', '2019', '2020', '2021', '2022'];
    $bVer  = [];
    $names = [];
    $paths = [];

    if (is_dir($dir))
    {
      foreach (glob($dir.'/*.adbarg') as $adbarg)
      {
        $contents = file_get_contents($adbarg);
        $lines    = explode("\n", $contents);
        $tInfo    = [];
        unset($tYear);
        foreach ($lines as $line)
        {
          if (preg_match('#^--([^=]+)=(.*)$#', $line, $matches))
          {
            $key         = $matches[1];
            $tInfo[$key] = $matches[2];
          }
        }

        if (!empty($tInfo['productName']))
        {
          $tStr = static::normalizeKey($tInfo['productName']);
          if ($tStr == $str)
          {
            $nme = $tInfo['productName'];
            $sap = !empty($tInfo['sapCode']) ? $tInfo['sapCode'] : null;
            $ver = !empty($tInfo['productVersion']) ? $tInfo['productVersion'] : null;

            foreach (self::PATH_TEMPLATES as $template)
            {
              foreach ($years as $year)
              {
                $plist = str_replace(['{name}', '{year}'], [$nme, $year], $template);

                if (file_exists($plist))
                {
                  if (false !== strpos($plist, $year))
                  {
                    $tYear = $year;
                  }

                  $paths[] = dirname($template, 2);
                }
              }
            }

            $names[]    = $tInfo['productName'];
            $bVer[$ver] = $tYear ?? $tInfo['productName'];
          }
        }
      }
    }

    if (isset($sap) && !empty($names) && !empty($bVer) && !empty($paths))
    {
      return ['sap' => $sap, 'names' => $names, 'baseVersions' => $bVer, 'path' => array_unique($paths)];
    }

    return null;
  }

  private static function normalizeKey($key)
  {
    return str_replace([' ', '_'], '-', strtolower($key));
  }
}
