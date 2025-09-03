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
      '/api/cache' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::yzJ3ZhxVfCsv0yrI',
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
            '_route' => 'generated::tT0OTwgWm17EgVNr',
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
            '_route' => 'generated::KgUsja8rWGsff8gP',
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
            '_route' => 'generated::47HMNBVz92zYEanM',
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
            '_route' => 'generated::mQmRPgRWTJXkK6BA',
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
      0 => '{^(?|/api/v1/(?|public/([^/]++)(?|(*:36)|/login(*:49))|dashboard/([^/]++)(?|(*:78)|/(?|p(?|roducts(?|(*:103)|/([^/]++)(?|(*:123)|/(?|images(?|(*:144))|sizes(?|(*:161)|/(?|sort(*:177)|([^/]++)(?|(*:196)))))))|urchases(?|(*:221)|/([^/]++)(?|(*:241)|/images(?|(*:259)))))|categories(?|(*:284)|/(?|([^/]++)(?|(*:307))|slug/([^/]++)(*:329)))|units(?|(*:347)|/([^/]++)(?|(*:367)))|suppliers(?|(*:389)|/(?|search/([^/]++)(*:416)|([^/]++)(?|(*:435))))|i(?|dentities(?|(*:462)|/([^/]++)(?|(*:482)))|mages/([^/]++)(*:506)))))|/storage/(.*)(*:531))/?$}sDu',
    ),
    3 => 
    array (
      36 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::H6Nzl4KLjQ6tIeyH',
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
      49 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::OgGyZO2HSEnisWCe',
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
      78 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mvNzO9TZD5Bkk182',
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
      103 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::vJzqUY6UHQwgsSJv',
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
            '_route' => 'generated::A0LbX5iiJjDmyGAa',
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
      123 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ZjmwkqEHNiQNgydi',
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
            '_route' => 'generated::gSaN4HBmOi6JRRbM',
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
            '_route' => 'generated::ajpvM4Q5YHidYA50',
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
      144 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YA1QXtttE0ZsJ0Z7',
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
            '_route' => 'generated::0Oz4pKz0rtwflJq9',
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
      161 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wWT4pnPN5uKOsDO9',
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
            '_route' => 'generated::Vq253me73khUCQKA',
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
      177 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NANivNLRukWnxoyx',
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
      196 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nMOmWSGhHXcYYGe9',
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
            '_route' => 'generated::uwmZhCgAQKgWadDc',
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
            '_route' => 'generated::5uoF27WbQ7yfHZnz',
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
      221 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::m4lqS6HBN2GNJ99q',
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
            '_route' => 'generated::Kgpp4h9GJ0EF2By9',
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
      241 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::kTGFJQG0HnO3CZUI',
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
            '_route' => 'generated::Or98p6SP75z9b4fX',
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
            '_route' => 'generated::QCYThKDxV4bOA7ms',
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
      259 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::i7GiSbgQvsCWkImJ',
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
            '_route' => 'generated::FZrQMXia2HwLLpK4',
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
      284 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::W4rjJpzIMk6xct8D',
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
            '_route' => 'generated::UHE9DFHBTOih7ShJ',
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
      307 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::LQPBMCq8MYmXY7e3',
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
            '_route' => 'generated::hhV4Zagklf93mAm1',
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
            '_route' => 'generated::OCKSO8J0taHEq3rA',
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
      329 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::F3VyZf0Ur8GotIpz',
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
      347 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::HYOzYFRNtaV3Sqt6',
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
            '_route' => 'generated::dvxE8pfzK0kKD3xE',
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
      367 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::GSoESdQ9O7ig8Wgh',
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
            '_route' => 'generated::hEcdJl9rzOR1qkkz',
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
            '_route' => 'generated::qi1ENO6ONw2WqRyq',
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
      389 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6D56uT9tSapgolcn',
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
            '_route' => 'generated::IcQ4Ozl2LKardK9k',
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
      416 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::0x6sJySQV5ufAn39',
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
      435 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::EKS7oOVfsNO3nsc7',
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
            '_route' => 'generated::MoB0qqQux181vzM4',
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
            '_route' => 'generated::1O7jklz9PvO3XBUa',
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
      462 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::h8Ism4wGz3AUBFn3',
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
            '_route' => 'generated::nhe3d7vdqqKr63FN',
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
      482 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pbaHVCMD6fiQm1Py',
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
            '_route' => 'generated::mn9VgkaLWTvZJOxt',
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
            '_route' => 'generated::ne7dNY9QgNtnGDqO',
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
      506 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::xs2x3MDuw8mtTb9k',
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
      531 => 
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
    'generated::yzJ3ZhxVfCsv0yrI' => 
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
        'as' => 'generated::yzJ3ZhxVfCsv0yrI',
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
    'generated::tT0OTwgWm17EgVNr' => 
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
        'as' => 'generated::tT0OTwgWm17EgVNr',
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
    'generated::KgUsja8rWGsff8gP' => 
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
        'as' => 'generated::KgUsja8rWGsff8gP',
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
    'generated::H6Nzl4KLjQ6tIeyH' => 
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
        'as' => 'generated::H6Nzl4KLjQ6tIeyH',
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
    'generated::OgGyZO2HSEnisWCe' => 
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
        'as' => 'generated::OgGyZO2HSEnisWCe',
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
    'generated::mvNzO9TZD5Bkk182' => 
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
        'as' => 'generated::mvNzO9TZD5Bkk182',
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
    'generated::vJzqUY6UHQwgsSJv' => 
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
        'as' => 'generated::vJzqUY6UHQwgsSJv',
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
    'generated::A0LbX5iiJjDmyGAa' => 
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
        'as' => 'generated::A0LbX5iiJjDmyGAa',
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
    'generated::ZjmwkqEHNiQNgydi' => 
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
        'as' => 'generated::ZjmwkqEHNiQNgydi',
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
    'generated::gSaN4HBmOi6JRRbM' => 
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
        'as' => 'generated::gSaN4HBmOi6JRRbM',
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
    'generated::ajpvM4Q5YHidYA50' => 
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
        'as' => 'generated::ajpvM4Q5YHidYA50',
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
    'generated::YA1QXtttE0ZsJ0Z7' => 
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
        'as' => 'generated::YA1QXtttE0ZsJ0Z7',
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
    'generated::0Oz4pKz0rtwflJq9' => 
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
        'as' => 'generated::0Oz4pKz0rtwflJq9',
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
    'generated::wWT4pnPN5uKOsDO9' => 
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
        'as' => 'generated::wWT4pnPN5uKOsDO9',
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
    'generated::Vq253me73khUCQKA' => 
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
        'as' => 'generated::Vq253me73khUCQKA',
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
    'generated::NANivNLRukWnxoyx' => 
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
        'as' => 'generated::NANivNLRukWnxoyx',
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
    'generated::nMOmWSGhHXcYYGe9' => 
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
        'as' => 'generated::nMOmWSGhHXcYYGe9',
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
    'generated::uwmZhCgAQKgWadDc' => 
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
        'as' => 'generated::uwmZhCgAQKgWadDc',
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
    'generated::5uoF27WbQ7yfHZnz' => 
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
        'as' => 'generated::5uoF27WbQ7yfHZnz',
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
    'generated::W4rjJpzIMk6xct8D' => 
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
        'as' => 'generated::W4rjJpzIMk6xct8D',
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
    'generated::UHE9DFHBTOih7ShJ' => 
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
        'as' => 'generated::UHE9DFHBTOih7ShJ',
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
    'generated::LQPBMCq8MYmXY7e3' => 
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
        'as' => 'generated::LQPBMCq8MYmXY7e3',
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
    'generated::hhV4Zagklf93mAm1' => 
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
        'as' => 'generated::hhV4Zagklf93mAm1',
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
    'generated::OCKSO8J0taHEq3rA' => 
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
        'as' => 'generated::OCKSO8J0taHEq3rA',
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
    'generated::F3VyZf0Ur8GotIpz' => 
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
        'as' => 'generated::F3VyZf0Ur8GotIpz',
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
    'generated::HYOzYFRNtaV3Sqt6' => 
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
        'as' => 'generated::HYOzYFRNtaV3Sqt6',
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
    'generated::dvxE8pfzK0kKD3xE' => 
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
        'as' => 'generated::dvxE8pfzK0kKD3xE',
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
    'generated::GSoESdQ9O7ig8Wgh' => 
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
        'as' => 'generated::GSoESdQ9O7ig8Wgh',
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
    'generated::hEcdJl9rzOR1qkkz' => 
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
        'as' => 'generated::hEcdJl9rzOR1qkkz',
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
    'generated::qi1ENO6ONw2WqRyq' => 
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
        'as' => 'generated::qi1ENO6ONw2WqRyq',
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
    'generated::6D56uT9tSapgolcn' => 
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
        'as' => 'generated::6D56uT9tSapgolcn',
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
    'generated::IcQ4Ozl2LKardK9k' => 
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
        'as' => 'generated::IcQ4Ozl2LKardK9k',
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
    'generated::0x6sJySQV5ufAn39' => 
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
        'as' => 'generated::0x6sJySQV5ufAn39',
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
    'generated::EKS7oOVfsNO3nsc7' => 
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
        'as' => 'generated::EKS7oOVfsNO3nsc7',
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
    'generated::MoB0qqQux181vzM4' => 
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
        'as' => 'generated::MoB0qqQux181vzM4',
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
    'generated::1O7jklz9PvO3XBUa' => 
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
        'as' => 'generated::1O7jklz9PvO3XBUa',
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
    'generated::m4lqS6HBN2GNJ99q' => 
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
        'as' => 'generated::m4lqS6HBN2GNJ99q',
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
    'generated::Kgpp4h9GJ0EF2By9' => 
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
        'as' => 'generated::Kgpp4h9GJ0EF2By9',
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
    'generated::kTGFJQG0HnO3CZUI' => 
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
        'as' => 'generated::kTGFJQG0HnO3CZUI',
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
    'generated::Or98p6SP75z9b4fX' => 
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
        'as' => 'generated::Or98p6SP75z9b4fX',
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
    'generated::QCYThKDxV4bOA7ms' => 
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
        'as' => 'generated::QCYThKDxV4bOA7ms',
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
    'generated::i7GiSbgQvsCWkImJ' => 
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
        'as' => 'generated::i7GiSbgQvsCWkImJ',
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
    'generated::FZrQMXia2HwLLpK4' => 
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
        'as' => 'generated::FZrQMXia2HwLLpK4',
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
    'generated::h8Ism4wGz3AUBFn3' => 
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
        'as' => 'generated::h8Ism4wGz3AUBFn3',
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
    'generated::nhe3d7vdqqKr63FN' => 
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
        'as' => 'generated::nhe3d7vdqqKr63FN',
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
    'generated::pbaHVCMD6fiQm1Py' => 
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
        'as' => 'generated::pbaHVCMD6fiQm1Py',
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
    'generated::mn9VgkaLWTvZJOxt' => 
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
        'as' => 'generated::mn9VgkaLWTvZJOxt',
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
    'generated::ne7dNY9QgNtnGDqO' => 
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
        'as' => 'generated::ne7dNY9QgNtnGDqO',
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
    'generated::xs2x3MDuw8mtTb9k' => 
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
        'as' => 'generated::xs2x3MDuw8mtTb9k',
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
    'generated::47HMNBVz92zYEanM' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:829:"function () {
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

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'C:\\\\xampp\\\\htdocs\\\\ecommerce\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"00000000000003170000000000000000";}}',
        'as' => 'generated::47HMNBVz92zYEanM',
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
    'generated::mQmRPgRWTJXkK6BA' => 
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
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000031b0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::mQmRPgRWTJXkK6BA',
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
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:45:"C:\\xampp\\htdocs\\ecommerce\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000003490000000000000000";}}',
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
