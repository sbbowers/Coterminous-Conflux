<?php

include_once getenv('FPATH').'/class/Auto.class.php';

StaticContent::refresh_cache();
//MealResource::serve_static_requests();
StaticContent::fetch_and_cache($_GET['__route']);

new MealResource();

