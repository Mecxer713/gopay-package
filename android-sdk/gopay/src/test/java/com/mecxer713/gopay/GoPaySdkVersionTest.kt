package com.mecxer713.gopay

import org.junit.Assert.assertEquals
import org.junit.Test

class GoPaySdkVersionTest {
    @Test
    fun sdkVersionIsExposedAsRuntimeConstant() {
        assertEquals("0.1.0", GoPaySdkVersion.VERSION)
    }
}
