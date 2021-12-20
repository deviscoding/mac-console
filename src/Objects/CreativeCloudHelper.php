<?php

namespace DevCoding\Mac\Objects;

/**
 * Helper class with methods to determine the specifics for various Adobe Creative Cloud applictions, including
 * SAP, preference paths, base name, base versions, and installed paths.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class CreativeCloudHelper
{
  const PATH_TEMPLATES = [
      '/Applications/Adobe {name} {year}/Adobe {name} {year}.app/Contents/Info.plist',
      '/Applications/Adobe {name} {year}/Adobe {name}.app/Contents/Info.plist',
      '/Applications/Adobe {name}/Adobe {name}.app/Contents/Info.plist',
      '/Applications/Adobe {name} CC/Adobe {name}.app/Contents/Info.plist',
  ];

  /** @var array */
  protected $adbarg;

  protected $data = [
      'illustrator' => [
          'sap'         => 'ILST',
          'name'        => 'Illustrator',
          'preferences' => [
              0 => 'Library/Preferences/Adobe/Adobe Illustrator/{version}',
              1 => 'Library/Preferences/Adobe Illustrator {majorVersion} Settings',
          ],
          'baseVersions' => [
              '21.0.0' => '2017',
              '22.0.0' => '2018',
              '23.0'   => '2019',
              '24.0'   => '2020',
              '25.0'   => '2021',
              '26.0'   => '2022',
          ],
      ],
      'indesign' => [
          'sap'         => 'IDSN',
          'name'        => 'InDesign',
          'preferences' => [
              0 => 'Library/Preferences/Adobe InDesign/Version {baseVersion}',
          ],
          'baseVersions' => [
              '12.0' => '2017',
              '13.0' => '2018',
              '14.0' => '2019',
              '15.0' => '2020',
              '16.0' => '2021',
              '17.0' => '2022',
          ],
      ],
      'photoshop' => [
          'sap'         => 'PHSP',
          'name'        => 'Photoshop',
          'preferences' => [
              0 => 'Library/Preferences/Adobe {name} {year} Settings',
              1 => 'Library/Preferences/Adobe {name} {year} Paths',
              2 => 'Library/Preferences/Adobe/Photoshop/{baseVersion}',
          ],
          'baseVersions' => [
              '18.0' => '2017',
              '19.0' => '2018',
              '20.0' => '2019',
              '21.0' => '2020',
              '22.0' => '2021',
              '23.0' => '2022',
          ],
      ],
      'bridge' => [
          'sap'         => 'KBRG',
          'name'        => 'Bridge',
          'preferences' => [
              0 => 'Library/Preferences/Adobe/Bridge/{version}',
              1 => 'Library/Preferences/com.adobe.bridge{major}.plist',
          ],
          'baseVersions' => [
              '7.0'  => '2017',
              '8.0'  => '2018',
              '9.0'  => '2019',
              '10.0' => '2020',
              '11.0' => '2021',
              '12.0' => '2022',
          ],
      ],
      'after-effects' => [
          'sap'         => 'AEFT',
          'name'        => 'After Effects',
          'preferences' => [
              0 => 'Library/Preferences/Adobe/After Effects/{baseVersion}',
          ],
          'baseVersions' => [
              '15.0' => '2018',
              '16.0' => '2019',
              '17.0' => '2020',
          ],
      ],
      'animate' => [
          'sap'         => 'FLPR',
          'name'        => 'Animate',
          'preferences' => [
              0 => 'Library/Preferences/Adobe/Animate/{year}',
              1 => 'Library/Preferences/Adobe/Animate Common/',
          ],
          'baseVersions' => [
              '18.0' => '2018',
              '19.0' => '2019',
              '20.0' => '2020',
              '21.0' => '2021',
          ],
      ],
      'premiere-pro' => [
          'sap'         => 'PPRO',
          'name'        => 'Premiere Pro',
          'preferences' => [
              0 => 'Documents/Adobe/Premiere Pro/{baseVersion}',
          ],
          'baseVersions' => [
              '12.0' => '2018',
              '13.0' => '2019',
              '14.0' => '2020',
          ],
      ],
      'xd' => [
          'sap'         => 'SPRK',
          'name'        => 'XD',
          'preferences' => [
              0 => 'Library/Application Support/Adobe/Adobe {name}',
              1 => 'Library/Application Support/Adobe.{name}',
          ],
          'baseVersions' => [
              '1.0.12'  => 'XD CC',
              '18.0.12' => 'XD',
          ],
      ],
      'dimension' => [
          'sap'         => 'ESHR',
          'name'        => 'Dimension',
          'preferences' => [
              0 => 'Library/Application Support/Adobe {name}',
          ],
          'baseVersions' => [
              '2.0' => 'Dimension CC',
              '3.0' => 'Dimension',
          ],
      ],
  ];

  /**
   * Returns an array of paths to the Info.plist of each installed Adobe Creative Cloud application that matches
   * the given slug.
   *
   * @param string $slug
   *
   * @return string[]
   */
  public function getInstalled($slug)
  {
    return $this->findInstalled($slug, $this->getBaseVersions($slug));
  }

  /**
   * @param string $slug
   *
   * @return mixed|null
   */
  public function getName($slug)
  {
    return $this->data[$slug]['name'] ?? null;
  }

  /**
   * Returns an array of paths to the given AdobeApplication's preferences.
   * @return string[]
   */
  public function getPreferences(AdobeApplication $app)
  {
    $paths = [];
    if ($templates = $this->data[$app->getSlug()])
    {
      $find = ['{name}', '{year}'];
      $repl = [$app->getName(), $app->getYear(), $app->getVersion(), $app->getBaseVersion(), $app->getVersion()->getMajor()];

      foreach ($templates as $pathTemplate)
      {
        $paths[] = str_replace($find, $repl, $pathTemplate);
      }
    }

    return $paths;
  }

  /**
   * @param string $slug
   *
   * @return string|null
   */
  public function getSap($slug)
  {
    if (!isset($this->data[$slug]['sapCode']))
    {
      $adbArgData = $this->getAdbArgData($slug);
      $lastData   = array_pop($adbArgData);

      $this->data[$slug]['sapCode'] = $lastData['sapCode'] ?? false;
    }

    return $this->data[$slug]['sapCode'] ?: null;
  }

  public function getBaseVersions($slug)
  {
    if (!isset($this->data[$slug]['baseVersions']))
    {
      // Initialize the array
      $this->data[$slug]['baseVersions'] = [];
      // Get the uninstall argument data
      $adbArgData = $this->getAdbArgData($slug);

      foreach ($adbArgData as $adbArgs)
      {
        // Get the Version and use it as the base version
        $bVer = $adbArgs['productVersion'];
        // Build the array of baseVersion => year/name
        $this->data[$slug]['baseVersions'][$bVer] = $adbArgs['year'] ?? $adbArgs['name'];
      }
    }

    return $this->data[$slug]['baseVersions'] ?: null;
  }

  /**
   * Returns the uninstall arguments as an array, using the given slug.
   *
   * @param string $slug
   *
   * @return array
   */
  protected function getAdbArgData($slug)
  {
    if (!isset($this->adbarg[$slug]))
    {
      // Initialize the Array
      $this->adbarg[$slug] = [];
      // Base Directory for all adbarg files
      $dir = '/Library/Application Support/Adobe/Uninstall';

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
            $tStr = $this->normalizeKey($tInfo['productName']);
            if ($tStr == $slug)
            {
              // Check for matching version & year
              $tVers = $tInfo['productVersion'] ?? null;
              $tYear = $tVers ? $this->getInstalledYearFromVersion($tVers) : null;

              // Compile info
              $this->adbarg[$slug][] = [
                  'name'     => $tInfo['productName'],
                  'sapCode'  => $tInfo['sapCode'] ?? null,
                  'platform' => $tInfo['productPlatform'] ?? null,
                  'version'  => $tVers,
                  'year'     => $tYear,
              ];
            }
          }
        }
      }
    }

    return $this->adbarg[$slug];
  }

  /**
   * Returns an array of paths to the Info.plist for each installed copy of the Adobe Creative Cloud application
   * referenced by the given slug.  If no $yearFilter array is given, years from 2015 to present are checked.
   *
   * @param string $slug        The slug representing the name of the Adobe Application
   * @param array $yearFilter   An array of years to check for installations.
   *
   * @return array              An array of paths to the Info.plist for each installed version that matches.
   */
  protected function findInstalled($slug, $yearFilter = [])
  {
    $installed  = [];
    $checkYears = !empty($yearFilter) ? $yearFilter : $this->getAdobeCreativeCloudYears();
    if ($name = $this->getName($slug))
    {
      foreach ($checkYears as $year)
      {
        foreach (self::PATH_TEMPLATES as $tmpl)
        {
          $find = ['{name}', '{year}'];
          $repl = [$name, $year];
          $path = str_replace($find, $repl, $tmpl);

          if (file_exists($path))
          {
            $installed[] = $path;
          }
        }
      }
    }

    return array_unique($installed);
  }

  /**
   * Returns the matching year from the given version.
   *
   * @param string $version
   * @param array  $installed
   *
   * @return string|null
   */
  protected function getInstalledYearFromVersion($version, $installed = [])
  {
    $majorVer = $this->getMajor($version);
    foreach ($installed as $path)
    {
      if (!isset($tYear))
      {
        $iApp   = new MacApplication(dirname($path, 2));
        $iMajor = $iApp->getShortVersion()->getMajor();

        if ($iMajor == $majorVer)
        {
          return $this->getYearFromPath($iApp->getPathname());
        }
      }
    }

    return null;
  }

  /**
   * Returns the slug from the given Adobe application path.
   *
   * @param string $path
   *
   * @return string|null
   */
  public function getSlugFromPath($path)
  {
    return $this->normalizeKey($this->getNameFromPath($path));
  }

  /**
   * Returns the application's base name from the given Adobe Creative Cloud application path.
   *
   * @param string $path
   *
   * @return name|null
   */
  public function getNameFromPath($path)
  {
    $ny = $this->getNameYearFromPath($path);

    return $ny['name'] ?? null;
  }

  /**
   * Returns the application's year from the given Adobe Creative Cloud application path.
   *
   * @param string $path
   *
   * @return string|null
   */
  public function getYearFromPath($path)
  {
    $ny = $this->getNameYearFromPath($path);

    return $ny['year'] ?? null;
  }

  /**
   * Returns the application's name and year from the given Adobe Creative Cloud application path.
   *
   * @param string $path    The path to the application.
   *
   * @return array          An array containing a 'name' and 'year' key and values.
   */
  protected function getNameYearFromPath($path)
  {
    if (preg_match('#/Adobe (?<name>[a-zA-Z\s]+)\s?(?<year>[1-9][0-9]+)?/#', $path, $m))
    {
      return ['name' => $m['name'] ?? null, 'year' => $m['year'] ?? null];
    }

    return [];
  }

  /**
   * @param string $vStr
   *
   * @return string|null
   */
  protected function getMajor($vStr)
  {
    try
    {
      return (new SemanticVersion($vStr))->getMajor();
    }
    catch (\Exception $e)
    {
      $parts = explode('.', $vStr);

      return reset($parts);
    }
  }

  /**
   * Returns an array of valid years for Adobe Creative Cloud applications.
   *
   * @return int[]|string[]
   */
  private function getAdobeCreativeCloudYears()
  {
    return array_keys(array_fill(2015, date('Y') + 1 - 2015, null));
  }

  /**
   * Slugifies the given Adobe Creative Cloud application name.
   *
   * @param string $key
   *
   * @return array|string|string[]
   */
  private function normalizeKey($key)
  {
    return str_replace(['CC', '_', ' '], ['', '-', '-'], strtolower($key));
  }
}
