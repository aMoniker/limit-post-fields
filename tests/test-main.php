<?php

class MainTest extends WP_UnitTestCase {

    public function testGetRules()
    {
        $rules = LimitPostFields::get_rules();
        $this->assertTrue(is_array($rules));
    }

    public function testSetRules($rules = array())
    {
        LimitPostFields::set_rules($rules);
        $this->assertEquals(LimitPostFields::get_rules(), $rules);
    }

}
