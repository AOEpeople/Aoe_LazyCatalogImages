<?php

/**
 * @see               Aoe_LazyCatalogImages_Helper_Catalog_Image
 *
 * @loadSharedFixture shared
 */
class Aoe_LazyCatalogImages_Test_Helper_Catalog_Image extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @coversNothing
     */
    public function checkHelper()
    {
        /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image $helper */
        $helper = Mage::helper('catalog/image');
        $this->assertInstanceOf('Aoe_LazyCatalogImages_Helper_Catalog_Image', $helper);
        $this->assertInstanceOf('Mage_Catalog_Helper_Image', $helper);

        /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image $helper */
        $helper = Mage::helper('Aoe_LazyCatalogImages/Catalog_Image');
        $this->assertInstanceOf('Aoe_LazyCatalogImages_Helper_Catalog_Image', $helper);

        return $helper;
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @depends      checkHelper
     *
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::getMaxCacheAge
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::setMaxCacheAge
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::_maxCacheAge
     *
     * @param mixed                                      $maxAge
     * @param int                                        $result
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function maxCacheAge($maxAge, $result, Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $maxAgeBefore = $helper->getMaxCacheAge();

        $this->assertSame($helper, $helper->setMaxCacheAge($maxAge));
        $this->assertEquals($result, $helper->getMaxCacheAge());
        $property = new ReflectionProperty($helper, '_maxCacheAge');
        $property->setAccessible(true);
        $this->assertEquals($result, $property->getValue($helper));

        $helper->setMaxCacheAge($maxAgeBefore);
    }

    /**
     * @test
     * @depends checkHelper
     *
     * @covers  Aoe_LazyCatalogImages_Helper_Catalog_Image::keepAspectRatio
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function keepAspectRatio(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $flagProperty = new ReflectionProperty($helper, '_keepAspectRatio');
        $flagProperty->setAccessible(true);

        $modelProperty = new ReflectionProperty($helper, '_model');
        $modelProperty->setAccessible(true);
        $originalModel = $modelProperty->getValue($helper);

        foreach (array(true, false) as $flag) {
            /** @var Mage_Catalog_Model_Product_Image|EcomDev_PHPUnit_Mock_Proxy $model */
            $model = $this->mockModel('catalog/product_image', array('setKeepAspectRatio'));
            $model->expects($this->once())->method('setKeepAspectRatio')->with($flag)->will($this->returnSelf());

            $flagProperty->setValue($helper, 'test');
            $modelProperty->setValue($helper, $model);

            $helper->keepAspectRatio($flag);

            $this->assertEquals($flag, $flagProperty->getValue($helper));
        }

        $modelProperty->setValue($helper, $originalModel);
    }

    /**
     * @test
     * @depends checkHelper
     *
     * @covers  Aoe_LazyCatalogImages_Helper_Catalog_Image::keepFrame
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function keepFrame(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $flagProperty = new ReflectionProperty($helper, '_keepFrame');
        $flagProperty->setAccessible(true);

        $modelProperty = new ReflectionProperty($helper, '_model');
        $modelProperty->setAccessible(true);
        $originalModel = $modelProperty->getValue($helper);

        foreach (array(true, false) as $flag) {
            /** @var Mage_Catalog_Model_Product_Image|EcomDev_PHPUnit_Mock_Proxy $model */
            $model = $this->mockModel('catalog/product_image', array('setKeepFrame'));
            $model->expects($this->once())->method('setKeepFrame')->with($flag)->will($this->returnSelf());

            $flagProperty->setValue($helper, 'test');
            $modelProperty->setValue($helper, $model);

            $helper->keepFrame($flag);

            $this->assertEquals($flag, $flagProperty->getValue($helper));
        }

        $modelProperty->setValue($helper, $originalModel);
    }

    /**
     * @test
     * @depends checkHelper
     *
     * @covers  Aoe_LazyCatalogImages_Helper_Catalog_Image::keepTransparency
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function keepTransparency(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $flagProperty = new ReflectionProperty($helper, '_keepTransparency');
        $flagProperty->setAccessible(true);

        $modelProperty = new ReflectionProperty($helper, '_model');
        $modelProperty->setAccessible(true);
        $originalModel = $modelProperty->getValue($helper);

        foreach (array(true, false) as $flag) {
            /** @var Mage_Catalog_Model_Product_Image|EcomDev_PHPUnit_Mock_Proxy $model */
            $model = $this->mockModel('catalog/product_image', array('setKeepTransparency'));
            $model->expects($this->once())->method('setKeepTransparency')->with($flag)->will($this->returnSelf());

            $flagProperty->setValue($helper, 'test');
            $modelProperty->setValue($helper, $model);

            $helper->keepTransparency($flag);

            $this->assertEquals($flag, $flagProperty->getValue($helper));
        }

        $modelProperty->setValue($helper, $originalModel);
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @depends      checkHelper
     *
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::generateToken
     *
     * @param array                                      $params
     * @param string                                     $expectedResult
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function generateToken(array $params, $expectedResult, Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $this->assertEquals($expectedResult, $helper->generateToken($params));
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @depends      checkHelper
     *
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::decodeToken
     *
     * @param string                                     $token
     * @param array|false                                $expectedResult
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function decodeToken($token, $expectedResult, Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $this->assertEquals($expectedResult, $helper->decodeToken($token));
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @depends      checkHelper
     * @depends      keepAspectRatio
     * @depends      keepFrame
     * @depends      keepTransparency
     * @depends      generateToken
     * @depends      decodeToken
     *
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::initFromToken
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function initFromToken(array $params, $result)
    {
        /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image|EcomDev_PHPUnit_Mock_Proxy $helper */
        $helper = $this->mockHelper(
            'Aoe_LazyCatalogImages/Catalog_Image',
            array(
                'decodeToken',
                'setImageFile',
                'setAngle',
                'resize',
                'setQuality',
                'keepAspectRatio',
                'keepTransparency',
                'keepFrame',
                'setWatermark',
                'setWatermarkImageOpacity',
                'setWatermarkPosition',
                'setWatermarkSize',
            )
        );

        $helper->expects($this->once())
            ->method('decodeToken')
            ->with('DUMMY')
            ->will($this->returnValue($params));

        if (!empty($params)) {
            $helper->expects($this->exactly(intval(isset($params['f']))))
                ->method('setImageFile')
                ->with(isset($params['f']) ? $params['f'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['fr']))))
                ->method('setAngle')
                ->with(isset($params['fr']) ? $params['fr'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['fw']) || isset($params['fh']))))
                ->method('resize')
                ->with(isset($params['fw']) ? $params['fw'] : null, isset($params['fh']) ? $params['fh'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['fq']))))
                ->method('setQuality')
                ->with(isset($params['fq']) ? $params['fq'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->once())
                ->method('keepAspectRatio')
                ->with((isset($params['fa']) && $params['fa']))
                ->will($this->returnSelf());
            $helper->expects($this->once())
                ->method('keepTransparency')
                ->with((isset($params['ft']) && $params['ft']))
                ->will($this->returnSelf());
            $helper->expects($this->once())
                ->method('keepFrame')
                ->with(isset($params['ff']) && $params['ff'])
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['wf']))))
                ->method('setWatermark')
                ->with(isset($params['wf']) ? $params['wf'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['wo']))))
                ->method('setWatermarkImageOpacity')
                ->with(isset($params['wo']) ? $params['wo'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['wp']))))
                ->method('setWatermarkPosition')
                ->with(isset($params['wp']) ? $params['wp'] : null)
                ->will($this->returnSelf());
            $helper->expects($this->exactly(intval(isset($params['ws']))))
                ->method('setWatermarkSize')
                ->with(isset($params['ws']) ? $params['ws'] : null)
                ->will($this->returnSelf());
        }

        if(is_string($result)) {
            $this->setExpectedException($result);
            $helper->initFromToken('DUMMY');
        } else {
            $this->assertEquals($result, $helper->initFromToken('DUMMY'));
        }
    }

    /**
     * @test
     * @depends checkHelper
     *
     * @covers  Aoe_LazyCatalogImages_Helper_Catalog_Image::getOutputFile
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function getOutputFile(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $this->markTestSkipped('TODO');
    }

    /**
     * @test
     * @depends checkHelper
     *
     * @covers  Aoe_LazyCatalogImages_Helper_Catalog_Image::__toString
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function _toString(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $this->markTestSkipped('TODO');
    }

    /**
     * @test
     * @depends      checkHelper
     *
     * @covers       Aoe_LazyCatalogImages_Helper_Catalog_Image::_reset
     *
     * @param Aoe_LazyCatalogImages_Helper_Catalog_Image $helper
     */
    public function _reset(Aoe_LazyCatalogImages_Helper_Catalog_Image $helper)
    {
        $maxCacheAge = new ReflectionProperty($helper, '_maxCacheAge');
        $maxCacheAge->setAccessible(true);
        $maxCacheAge->setValue($helper, 'test');

        $keepAspectRatio = new ReflectionProperty($helper, '_keepAspectRatio');
        $keepAspectRatio->setAccessible(true);
        $keepAspectRatio->setValue($helper, 'test');

        $keepFrame = new ReflectionProperty($helper, '_keepFrame');
        $keepFrame->setAccessible(true);
        $keepFrame->setValue($helper, 'test');

        $keepTransparency = new ReflectionProperty($helper, '_keepTransparency');
        $keepTransparency->setAccessible(true);
        $keepTransparency->setValue($helper, 'test');

        $reset = new ReflectionMethod($helper, '_reset');
        $reset->setAccessible(true);
        $reset->invoke($helper);

        $this->assertEquals(3600, $maxCacheAge->getValue($helper));
        $this->assertEquals(true, $keepAspectRatio->getValue($helper));
        $this->assertEquals(true, $keepFrame->getValue($helper));
        $this->assertEquals(true, $keepTransparency->getValue($helper));
    }
}
