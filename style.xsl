<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8"/>
    
    <xsl:template match="/">
        <html>
            <head>
                <title>Indeed Job Feed</title>
            </head>
            <body>
                <xsl:apply-templates/>
            </body>
        </html>
    </xsl:template>
    
    <xsl:template match="*">
        <div style="margin-left: 20px;">
            <xsl:value-of select="name()"/>: <xsl:value-of select="text()"/>
            <xsl:apply-templates select="*"/>
        </div>
    </xsl:template>
</xsl:stylesheet>

