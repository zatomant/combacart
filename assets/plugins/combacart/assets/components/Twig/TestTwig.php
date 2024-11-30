<?php


use Twig\Error\LoaderError;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class CC_TestTwig extends AbstractExtension
{

    public function getTestsExt($twig): array
    {
        return array(
            new TwigTest('ondisk', function ($template) use ($twig) {
                try {
                    // Спроба завантажити шаблон
                    $twig->load($template);
                    return true;
                } catch (LoaderError $e) {
                    return false;
                }
            })
        );
    }

}