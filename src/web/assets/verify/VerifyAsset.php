<?php

namespace born05\twofactorauthentication\web\assets\verify;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for Social
 */
class VerifyAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'verify.css',
        ];
        
        $this->js = [
            'verify.js',
        ];

        parent::init();
    }
}
