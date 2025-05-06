<?php

namespace Comba\Core;

use ReflectionClass;
use Twig\Extension\AbstractExtension;

class CombaComponentTwig
{

    static public function register($twig, string $path = __DIR__)
    {

        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {

                    if ($file == '.' || $file == '..' || strpos($file, 'Twig') === false) {
                        continue;
                    }

                    $_tempclass = $extname = "CC_" . basename($file, ".php");
                    require_once $path . $file;

                    if (class_exists($_tempclass)) {
                        // Визначаємо батьківський клас
                        $reflection = new ReflectionClass($_tempclass);

                        if ($reflection->isSubclassOf(AbstractExtension::class)) {
                            $_tcn = new $_tempclass();
                            $twig->addExtension($_tcn);
                            if (method_exists($_tcn,'getTestsExt')) {
                                if ($tests = $_tcn->getTestsExt($twig)) {
                                    foreach ($tests as $test) {
                                        $twig->addTest($test);
                                    }
                                }
                            }
                        }
                    }

                }
                closedir($dh);
            }
        }
    }

}
