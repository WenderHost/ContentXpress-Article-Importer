<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:search="http://marklogic.com/appservices/search"
                xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <xsl:output encoding="UTF-8" indent="yes" method="xml" />

    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="highlight|search:highlight">
        <span class="highlight"><xsl:value-of select="."/></span>
    </xsl:template>

</xsl:stylesheet>