<?php

use App\Rules\HexColor;

function runHexColor(mixed $value): bool
{
    $passed = true;
    (new HexColor())->validate('color', $value, function () use (&$passed) {
        $passed = false;
    });
    return $passed;
}

test('HexColor passes valid 6-digit lowercase hex', function () {
    expect(runHexColor('#ff0000'))->toBeTrue();
    expect(runHexColor('#000000'))->toBeTrue();
    expect(runHexColor('#ffffff'))->toBeTrue();
    expect(runHexColor('#1a2b3c'))->toBeTrue();
});

test('HexColor passes valid 6-digit uppercase hex', function () {
    expect(runHexColor('#FF0000'))->toBeTrue();
    expect(runHexColor('#AABBCC'))->toBeTrue();
});

test('HexColor fails without hash prefix', function () {
    expect(runHexColor('ff0000'))->toBeFalse();
    expect(runHexColor('000000'))->toBeFalse();
});

test('HexColor fails with wrong length', function () {
    expect(runHexColor('#fff'))->toBeFalse();
    expect(runHexColor('#fffffff'))->toBeFalse();
    expect(runHexColor('#fffff'))->toBeFalse();
});

test('HexColor fails with non-hex characters', function () {
    expect(runHexColor('#gggggg'))->toBeFalse();
    expect(runHexColor('#xyz123'))->toBeFalse();
    expect(runHexColor('#12345g'))->toBeFalse();
});
