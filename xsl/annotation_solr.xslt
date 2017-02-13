<?xml version="1.0" encoding="UTF-8"?>
<!-- ORALHISTORIES TRANSCRIPT -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:foxml="info:fedora/fedora-system:def/foxml#" xmlns:dcterms="http://purl.org/dc/terms/">
    <xsl:template match="foxml:datastream[@ID='WADM_SEARCH']/foxml:datastreamVersion[last()]" name="index_ANNOTATION">
        <xsl:param name="content"/>
            <field name="annotation_title">
                <xsl:value-of select="$content//title"/>
            </field>
            <field name="annotation_value">
                <xsl:value-of select="$content//textvalue"/>
            </field>
            <field name="annotation_parent">
                <xsl:value-of select="$content//target"/>
            </field>

            <xsl:if test="$content//rangeTimeStart">
                <field name="annotation_rangeTimeStart">
                    <xsl:value-of select="$content//rangeTimeStart"/>
                </field>
                <field name="annotation_rangeTimeEnd">
                    <xsl:value-of select="$content//rangeTimeEnd"/>
                </field>
            </xsl:if>

    </xsl:template>

</xsl:stylesheet>

