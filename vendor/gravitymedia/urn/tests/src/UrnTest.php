<?php
/**
 * This file is part of the Urn project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\UrnTest;

use GravityMedia\Urn\Urn;

/**
 * URN test class.
 *
 * @package GravityMedia\UrnTest
 *
 * @covers  GravityMedia\Urn\Urn
 */
class UrnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test URN construction from malformed string throws exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The string argument appears to be malformed
     */
    public function testUrnConstructionFromMalformedStringThrowsException()
    {
        Urn::fromString('::');
    }

    /**
     * Test URN construction from string.
     *
     * @dataProvider provideUrnStrings()
     *
     * @param string $input
     * @param string $output
     * @param array  $expectations
     */
    public function testUrnConstructionFromString($input, $output, array $expectations)
    {
        $urn = Urn::fromString($input);

        $this->assertSame($output, (string)$urn);
        $this->assertSame($expectations['nid'], $urn->getNamespaceIdentifier());
        $this->assertSame($expectations['nss'], $urn->getNamespaceSpecificString());
    }

    /**
     * Provide URN strings.
     *
     * @return array
     */
    public function provideUrnStrings()
    {
        return [
            [
                'urn:example:animal:ferret:nose',
                'urn:example:animal:ferret:nose',
                [
                    'nid' => 'example',
                    'nss' => 'animal:ferret:nose'
                ]
            ],
            [
                'urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e',
                'urn:this-is-an-example:t(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e',
                [
                    'nid' => 'this-is-an-example',
                    'nss' => 't(h)i+s,i-s.a:n=o@t;h$e_r!e*x\'a%m/p?l#e'
                ]
            ]
        ];
    }
}
