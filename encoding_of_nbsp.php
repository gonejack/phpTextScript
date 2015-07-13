<?php

var_dump(trim(html_entity_decode('&nbsp;'), " \t\n\r\0\x0B\xC2\xA0"));

var_dump(unpack('H*', pack('a*', html_entity_decode('&nbsp;'))));

var_dump(unpack('a*', pack('H*', 'c2a0')));

var_dump(unpack('H*', str_replace("\xc2\xa0", ' ', html_entity_decode('&nbsp;'))));

var_dump(unpack('H*', pack('a*', '　'))); #chinese space