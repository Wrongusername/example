<?php
$currlang=getCurrentLang();
html('css','screenup-decline3');
html('js','locale-screnup-decline3-' . $currlang);
html('js','screenup-decline3');
$declineReasons=lib::get('declineReasons');