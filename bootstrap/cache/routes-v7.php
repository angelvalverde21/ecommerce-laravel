<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/oauth/token' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.token',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/oauth/authorize' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.authorizations.authorize',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'passport.authorizations.approve',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'passport.authorizations.deny',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/oauth/device' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.device',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/oauth/device/code' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.device.code',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/oauth/token/refresh' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.token.refresh',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/oauth/device/authorize' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'passport.device.authorizations.authorize',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'passport.device.authorizations.approve',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'passport.device.authorizations.deny',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/shopify/products' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YeHu7ebkYNNxlSzx',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::0FDFAN2GVK5Mtmfk',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/shopify/reports' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4UIaDUmVvDapPsSq',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/shopify/reports/top' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::COWJ26xuLzxtEYjt',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/shopify/orders' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FePepfbxiVcF51KU',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::16hDfJ0zoGgSwG18',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/shopify/pasarelas/mercadopago/create-link' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::unvMkfyOrvwR9X7T',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/cache' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::I1GJ4WNOnEehseBi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/link' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::64PULBMgjId62My6',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/v1/public/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::5xiui56A2MOhjHAW',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FESfcAmcZsujDIDi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dz5JGVPlgqBCSMvp',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/api/v1/(?|shopify/(?|products/(?|search/([^/]++)(*:56)|([^/]++)(?|(*:74)))|reports/daily/([^/]++)(*:105)|orders/([^/]++)(?|(*:131)))|public/([^/]++)(?|(*:159)|/login(*:173))|dashboard/([^/]++)(?|(*:203)|/(?|p(?|roducts(?|(*:229)|/([^/]++)(?|(*:249)|/(?|images(?|(*:270))|sizes(?|(*:287)|/(?|sort(*:303)|([^/]++)(?|(*:322))))|attribute(?|s(?|(*:349)|/([^/]++)(?|(*:369)))|\\-values(?|(*:390)|/([^/]++)(?|(*:410)))))))|urchases(?|(*:435)|/([^/]++)(?|(*:455)|/images(?|(*:473)))))|brands(?|(*:494)|/([^/]++)(?|(*:514)))|categories(?|(*:537)|/(?|([^/]++)(?|(*:560))|slug/([^/]++)(*:582)))|units(?|(*:600)|/([^/]++)(?|(*:620)))|suppliers(?|(*:642)|/(?|search/([^/]++)(*:669)|([^/]++)(?|(*:688))))|i(?|dentities(?|(*:715)|/([^/]++)(?|(*:735)))|mages/([^/]++)(*:759)))))|/storage/(.*)(*:784))/?$}sDu',
    ),
    3 => 
    array (
      56 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::3XsvY47nb4cQX7iy',
          ),
          1 => 
          array (
            0 => 'search',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      74 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::OhgprGVpW5E7cQhG',
          ),
          1 => 
          array (
            0 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::IzFhpFFJ3OnUFQdC',
          ),
          1 => 
          array (
            0 => 'product_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JoKU5WJbdpLSjma0',
          ),
          1 => 
          array (
            0 => 'product_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      105 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pieVsrwE911OPDYV',
          ),
          1 => 
          array (
            0 => 'days',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      131 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::P1m4L3JmVl8XtgPE',
          ),
          1 => 
          array (
            0 => 'order_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::EcWDlcC8al7etHmV',
          ),
          1 => 
          array (
            0 => 'order_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::rJ56yJsKvsgyvUId',
          ),
          1 => 
          array (
            0 => 'order_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      159 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::d3g1cssR7dPQWno7',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      173 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::obHMXsfwGff7ZfC7',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      203 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lvZhASU4WWOI8C3M',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      229 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6tj8YATlrqJGcQdT',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::IYRPtON6VGm31R9j',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      249 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lLdCLA204nkbwa6z',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::r6iyB26YtbDnS1Yy',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JDpsn88FBxzI5a7T',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      270 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ucjbma1ppwGxmVm3',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::sLlwYiIhKy16j6qV',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      287 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JZEweAFZ9jc9VOqY',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lsVWfK6mCJzVpgFk',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      303 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::w0YqE6ctvzHrsnIx',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      322 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::orQp7nGKOWdNXOri',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'size_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6obDNDdusCkkpx3p',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'size_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mDY6GMIqPUTEZ2Yv',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'size_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      349 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::vBvuUeRFmuEvHFz5',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::CTRV2dwqQEiq5fzI',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      369 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::rldbpfOGc9zEbYJD',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'attribute_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::X5fX8iHC0jTBlGCY',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'attribute_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Kunbzd2apfVfg1JG',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'attribute_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      390 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::40AL1hO7mJ71EWIq',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::SE80SDu58LwDJWi1',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      410 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6bvMzb0vB3Ti1G06',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'brand_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::aCHySDcWYifTdT0U',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'brand_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::q6kQEiZJXueVrNqR',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'product_id',
            2 => 'brand_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      435 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::V5ZNBQwg03hwW0cl',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::e3zS3o2eXYLqqkml',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      455 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::yAYLfylW6pGsmGkc',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'purchase_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::xkAB4QLSWwwFo2f2',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'purchase_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::LtsbZeQpsWfJRw3e',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'purchase_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      473 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::kY0F8pWTzz4CAIzm',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'purchase_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::TiYuPZRgCr2qPoTZ',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'purchase_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      494 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lViSxrUIFaKEloyx',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Ly0ZwjAVJuDhn5q0',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      514 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Y6GsKjiAERdzm7og',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'brand_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::PP25VfWDRZ8p5Jir',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'brand_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::gfFQfl8RolVvByXO',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'brand_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      537 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::B9he6RD6OBrogPqR',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FzfpDo3ytHPmRp7P',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      560 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::rgxTKyDOYnOxXee7',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'category_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JRIKCXpX3g01oco8',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'category_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YUROoKHk2j34XL7T',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'category_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      582 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::212D79LGGnfl1M8T',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'slug',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      600 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Z1qoeKFGO3TWRwNs',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QUt8Mk6xD51TZbkc',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      620 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dXa9tLeAmwR1HgGt',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'unit_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::fQaRNzPNhH7RaI8w',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'unit_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lyOl5N7E4rQV5YKq',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'unit_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      642 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QkNmSs8yTWzEHSzo',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::UqEgW0KT9G6SJuMo',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      669 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::B6DvlccuutvLroSC',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'search',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      688 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::tk7w7V0tyqklFkAX',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'supplier_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4GAnOLHixF1XdG5Y',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'supplier_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ePydhVxZ3Vnt25Y1',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'supplier_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      715 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::XXUlL9u6BaQaKRei',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::A1t0vmUkeJpS2HzI',
          ),
          1 => 
          array (
            0 => 'store',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      735 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::bdQYKFD3s2hsNyAB',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'identity_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::V5Q7zDmtaAfZYClh',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'identity_id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::daVZdu00qz9Fw29p',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'identity_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      759 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::g5r2IEU8cmP9uuS6',
          ),
          1 => 
          array (
            0 => 'store',
            1 => 'image_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      784 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'passport.token' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'oauth/token',
      'action' => 
      array (
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\AccessTokenController@issueToken',
        'as' => 'passport.token',
        'middleware' => 'throttle',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\AccessTokenController@issueToken',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.authorizations.authorize' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'oauth/authorize',
      'action' => 
      array (
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\AuthorizationController@authorize',
        'as' => 'passport.authorizations.authorize',
        'middleware' => 'web',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\AuthorizationController@authorize',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.device' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'oauth/device',
      'action' => 
      array (
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\DeviceUserCodeController@__invoke',
        'as' => 'passport.device',
        'middleware' => 'web',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\DeviceUserCodeController',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.device.code' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'oauth/device/code',
      'action' => 
      array (
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\DeviceCodeController@__invoke',
        'as' => 'passport.device.code',
        'middleware' => 'throttle',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\DeviceCodeController',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.token.refresh' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'oauth/token/refresh',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\TransientTokenController@refresh',
        'as' => 'passport.token.refresh',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\TransientTokenController@refresh',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.authorizations.approve' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'oauth/authorize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\ApproveAuthorizationController@approve',
        'as' => 'passport.authorizations.approve',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\ApproveAuthorizationController@approve',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.authorizations.deny' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'oauth/authorize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\DenyAuthorizationController@deny',
        'as' => 'passport.authorizations.deny',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\DenyAuthorizationController@deny',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.device.authorizations.authorize' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'oauth/device/authorize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\DeviceAuthorizationController@__invoke',
        'as' => 'passport.device.authorizations.authorize',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\DeviceAuthorizationController',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.device.authorizations.approve' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'oauth/device/authorize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\ApproveDeviceAuthorizationController@__invoke',
        'as' => 'passport.device.authorizations.approve',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\ApproveDeviceAuthorizationController',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'passport.device.authorizations.deny' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'oauth/device/authorize',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:api',
        ),
        'uses' => 'Laravel\\Passport\\Http\\Controllers\\DenyDeviceAuthorizationController@__invoke',
        'as' => 'passport.device.authorizations.deny',
        'controller' => 'Laravel\\Passport\\Http\\Controllers\\DenyDeviceAuthorizationController',
        'namespace' => 'Laravel\\Passport\\Http\\Controllers',
        'prefix' => 'oauth',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YeHu7ebkYNNxlSzx' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products',
        'where' => 
        array (
        ),
        'as' => 'generated::YeHu7ebkYNNxlSzx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::3XsvY47nb4cQX7iy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/products/search/{search}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@search',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@search',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products',
        'where' => 
        array (
        ),
        'as' => 'generated::3XsvY47nb4cQX7iy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::0FDFAN2GVK5Mtmfk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/shopify/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products',
        'where' => 
        array (
        ),
        'as' => 'generated::0FDFAN2GVK5Mtmfk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::OhgprGVpW5E7cQhG' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::OhgprGVpW5E7cQhG',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::IzFhpFFJ3OnUFQdC' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/shopify/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::IzFhpFFJ3OnUFQdC',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JoKU5WJbdpLSjma0' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/shopify/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ProductShopifyController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::JoKU5WJbdpLSjma0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4UIaDUmVvDapPsSq' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/reports',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::4UIaDUmVvDapPsSq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::COWJ26xuLzxtEYjt' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/reports/top',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@topProducts',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@topProducts',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::COWJ26xuLzxtEYjt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::pieVsrwE911OPDYV' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/reports/daily/{days}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@dailyOrders',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\ReportShopifyController@dailyOrders',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::pieVsrwE911OPDYV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FePepfbxiVcF51KU' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/orders',
        'where' => 
        array (
        ),
        'as' => 'generated::FePepfbxiVcF51KU',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::16hDfJ0zoGgSwG18' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/shopify/orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/orders',
        'where' => 
        array (
        ),
        'as' => 'generated::16hDfJ0zoGgSwG18',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::P1m4L3JmVl8XtgPE' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/shopify/orders/{order_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/orders/{order_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::P1m4L3JmVl8XtgPE',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::EcWDlcC8al7etHmV' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/shopify/orders/{order_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/orders/{order_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::EcWDlcC8al7etHmV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::rJ56yJsKvsgyvUId' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/shopify/orders/{order_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Shopify\\OrderShopifyController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/orders/{order_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::rJ56yJsKvsgyvUId',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::unvMkfyOrvwR9X7T' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/shopify/pasarelas/mercadopago/create-link',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Pasarelas\\MercadoPagoController@createLink',
        'controller' => 'App\\Http\\Controllers\\Api\\Pasarelas\\MercadoPagoController@createLink',
        'namespace' => NULL,
        'prefix' => 'api/v1/shopify/pasarelas/mercadopago',
        'where' => 
        array (
        ),
        'as' => 'generated::unvMkfyOrvwR9X7T',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::I1GJ4WNOnEehseBi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cache',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:290:"function () {

    \\Illuminate\\Support\\Facades\\Artisan::call(\'cache:clear\');
    \\Illuminate\\Support\\Facades\\Artisan::call(\'config:cache\');
    \\Illuminate\\Support\\Facades\\Artisan::call(\'route:cache\');
    \\Illuminate\\Support\\Facades\\Artisan::call(\'view:cache\');

    return "Cacheado!x";
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003130000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::I1GJ4WNOnEehseBi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::64PULBMgjId62My6' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/link',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:78:"function () {
    \\Illuminate\\Support\\Facades\\Artisan::call(\'storage:link\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000003110000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::64PULBMgjId62My6',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::5xiui56A2MOhjHAW' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/public/register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\public\\StorePublicController@register',
        'controller' => 'App\\Http\\Controllers\\Api\\public\\StorePublicController@register',
        'namespace' => NULL,
        'prefix' => 'api/v1/public',
        'where' => 
        array (
        ),
        'as' => 'generated::5xiui56A2MOhjHAW',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::d3g1cssR7dPQWno7' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/public/{store}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'App\\Http\\Middleware\\VerifyStore',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\public\\StorePublicController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\public\\StorePublicController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/public/{store}',
        'where' => 
        array (
        ),
        'as' => 'generated::d3g1cssR7dPQWno7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::obHMXsfwGff7ZfC7' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/public/{store}/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'App\\Http\\Middleware\\VerifyStore',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AuthApiController@login',
        'controller' => 'App\\Http\\Controllers\\Api\\AuthApiController@login',
        'namespace' => NULL,
        'prefix' => 'api/v1/public/{store}',
        'where' => 
        array (
        ),
        'as' => 'generated::obHMXsfwGff7ZfC7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lvZhASU4WWOI8C3M' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\DashboardController@getDashboard',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\DashboardController@getDashboard',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}',
        'where' => 
        array (
        ),
        'as' => 'generated::lvZhASU4WWOI8C3M',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6tj8YATlrqJGcQdT' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products',
        'where' => 
        array (
        ),
        'as' => 'generated::6tj8YATlrqJGcQdT',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::IYRPtON6VGm31R9j' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products',
        'where' => 
        array (
        ),
        'as' => 'generated::IYRPtON6VGm31R9j',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lLdCLA204nkbwa6z' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::lLdCLA204nkbwa6z',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::r6iyB26YtbDnS1Yy' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::r6iyB26YtbDnS1Yy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JDpsn88FBxzI5a7T' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\ProductDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::JDpsn88FBxzI5a7T',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ucjbma1ppwGxmVm3' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/images',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageProductController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageProductController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/images',
        'where' => 
        array (
        ),
        'as' => 'generated::ucjbma1ppwGxmVm3',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::sLlwYiIhKy16j6qV' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/images',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageProductController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageProductController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/images',
        'where' => 
        array (
        ),
        'as' => 'generated::sLlwYiIhKy16j6qV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JZEweAFZ9jc9VOqY' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes',
        'where' => 
        array (
        ),
        'as' => 'generated::JZEweAFZ9jc9VOqY',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lsVWfK6mCJzVpgFk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes',
        'where' => 
        array (
        ),
        'as' => 'generated::lsVWfK6mCJzVpgFk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::w0YqE6ctvzHrsnIx' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/sort',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@sort',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@sort',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes',
        'where' => 
        array (
        ),
        'as' => 'generated::w0YqE6ctvzHrsnIx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::orQp7nGKOWdNXOri' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::orQp7nGKOWdNXOri',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6obDNDdusCkkpx3p' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::6obDNDdusCkkpx3p',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::mDY6GMIqPUTEZ2Yv' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SizeDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/sizes/{size_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::mDY6GMIqPUTEZ2Yv',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::vBvuUeRFmuEvHFz5' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attributes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attributes',
        'where' => 
        array (
        ),
        'as' => 'generated::vBvuUeRFmuEvHFz5',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::CTRV2dwqQEiq5fzI' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attributes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attributes',
        'where' => 
        array (
        ),
        'as' => 'generated::CTRV2dwqQEiq5fzI',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::rldbpfOGc9zEbYJD' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::rldbpfOGc9zEbYJD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::X5fX8iHC0jTBlGCY' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::X5fX8iHC0jTBlGCY',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Kunbzd2apfVfg1JG' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\AttributeDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attributes/{attribute_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::Kunbzd2apfVfg1JG',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::40AL1hO7mJ71EWIq' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\AttributeValueDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\AttributeValueDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values',
        'where' => 
        array (
        ),
        'as' => 'generated::40AL1hO7mJ71EWIq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::SE80SDu58LwDJWi1' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\AttributeValueDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\AttributeValueDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values',
        'where' => 
        array (
        ),
        'as' => 'generated::SE80SDu58LwDJWi1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6bvMzb0vB3Ti1G06' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\AttributeValueDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\AttributeValueDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::6bvMzb0vB3Ti1G06',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::aCHySDcWYifTdT0U' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\AttributeValueDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\AttributeValueDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::aCHySDcWYifTdT0U',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::q6kQEiZJXueVrNqR' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\AttributeValueDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\AttributeValueDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/products/{product_id}/attribute-values/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::q6kQEiZJXueVrNqR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lViSxrUIFaKEloyx' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/brands',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/brands',
        'where' => 
        array (
        ),
        'as' => 'generated::lViSxrUIFaKEloyx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Ly0ZwjAVJuDhn5q0' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/brands',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/brands',
        'where' => 
        array (
        ),
        'as' => 'generated::Ly0ZwjAVJuDhn5q0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Y6GsKjiAERdzm7og' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/brands/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/brands/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::Y6GsKjiAERdzm7og',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::PP25VfWDRZ8p5Jir' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/brands/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/brands/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::PP25VfWDRZ8p5Jir',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::gfFQfl8RolVvByXO' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/brands/{brand_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\BrandDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/brands/{brand_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::gfFQfl8RolVvByXO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::B9he6RD6OBrogPqR' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::B9he6RD6OBrogPqR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FzfpDo3ytHPmRp7P' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::FzfpDo3ytHPmRp7P',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::rgxTKyDOYnOxXee7' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories/{category_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories/{category_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::rgxTKyDOYnOxXee7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JRIKCXpX3g01oco8' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories/{category_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories/{category_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::JRIKCXpX3g01oco8',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YUROoKHk2j34XL7T' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories/{category_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories/{category_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::YUROoKHk2j34XL7T',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::212D79LGGnfl1M8T' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/categories/slug/{slug}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@showListBySlug',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\CategoryDashboardController@showListBySlug',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::212D79LGGnfl1M8T',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Z1qoeKFGO3TWRwNs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/units',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/units',
        'where' => 
        array (
        ),
        'as' => 'generated::Z1qoeKFGO3TWRwNs',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QUt8Mk6xD51TZbkc' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/units',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/units',
        'where' => 
        array (
        ),
        'as' => 'generated::QUt8Mk6xD51TZbkc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dXa9tLeAmwR1HgGt' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/units/{unit_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/units/{unit_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::dXa9tLeAmwR1HgGt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::fQaRNzPNhH7RaI8w' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/units/{unit_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/units/{unit_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::fQaRNzPNhH7RaI8w',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lyOl5N7E4rQV5YKq' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/units/{unit_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\UnitDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/units/{unit_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::lyOl5N7E4rQV5YKq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QkNmSs8yTWzEHSzo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers',
        'where' => 
        array (
        ),
        'as' => 'generated::QkNmSs8yTWzEHSzo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::UqEgW0KT9G6SJuMo' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers',
        'where' => 
        array (
        ),
        'as' => 'generated::UqEgW0KT9G6SJuMo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::B6DvlccuutvLroSC' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers/search/{search}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@search',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@search',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers',
        'where' => 
        array (
        ),
        'as' => 'generated::B6DvlccuutvLroSC',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::tk7w7V0tyqklFkAX' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::tk7w7V0tyqklFkAX',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4GAnOLHixF1XdG5Y' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::4GAnOLHixF1XdG5Y',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ePydhVxZ3Vnt25Y1' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\SupplierDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/suppliers/{supplier_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::ePydhVxZ3Vnt25Y1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::V5ZNBQwg03hwW0cl' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases',
        'where' => 
        array (
        ),
        'as' => 'generated::V5ZNBQwg03hwW0cl',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::e3zS3o2eXYLqqkml' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases',
        'where' => 
        array (
        ),
        'as' => 'generated::e3zS3o2eXYLqqkml',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::yAYLfylW6pGsmGkc' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::yAYLfylW6pGsmGkc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::xkAB4QLSWwwFo2f2' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::xkAB4QLSWwwFo2f2',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::LtsbZeQpsWfJRw3e' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\PurchaseDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases/{purchase_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::LtsbZeQpsWfJRw3e',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::kY0F8pWTzz4CAIzm' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases/{purchase_id}/images',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImagePurchaseController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImagePurchaseController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases/{purchase_id}/images',
        'where' => 
        array (
        ),
        'as' => 'generated::kY0F8pWTzz4CAIzm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::TiYuPZRgCr2qPoTZ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/purchases/{purchase_id}/images',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImagePurchaseController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImagePurchaseController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/purchases/{purchase_id}/images',
        'where' => 
        array (
        ),
        'as' => 'generated::TiYuPZRgCr2qPoTZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::XXUlL9u6BaQaKRei' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/identities',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@index',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/identities',
        'where' => 
        array (
        ),
        'as' => 'generated::XXUlL9u6BaQaKRei',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::A1t0vmUkeJpS2HzI' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/v1/dashboard/{store}/identities',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@store',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/identities',
        'where' => 
        array (
        ),
        'as' => 'generated::A1t0vmUkeJpS2HzI',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::bdQYKFD3s2hsNyAB' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/v1/dashboard/{store}/identities/{identity_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@show',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/identities/{identity_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::bdQYKFD3s2hsNyAB',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::V5Q7zDmtaAfZYClh' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/v1/dashboard/{store}/identities/{identity_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@update',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/identities/{identity_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::V5Q7zDmtaAfZYClh',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::daVZdu00qz9Fw29p' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/identities/{identity_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\IdentityDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/identities/{identity_id}',
        'where' => 
        array (
        ),
        'as' => 'generated::daVZdu00qz9Fw29p',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::g5r2IEU8cmP9uuS6' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/v1/dashboard/{store}/images/{image_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageDashboardController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\Dashboard\\Images\\ImageDashboardController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/v1/dashboard/{store}/images',
        'where' => 
        array (
        ),
        'as' => 'generated::g5r2IEU8cmP9uuS6',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FESfcAmcZsujDIDi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:827:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'C:\\\\xampp\\\\htdocs\\\\shopify\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"00000000000003170000000000000000";}}',
        'as' => 'generated::FESfcAmcZsujDIDi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dz5JGVPlgqBCSMvp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:44:"function () {
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000032b0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::dz5JGVPlgqBCSMvp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:43:"C:\\xampp\\htdocs\\shopify\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000003670000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
