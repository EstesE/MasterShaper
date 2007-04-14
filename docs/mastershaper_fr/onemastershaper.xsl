<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version='1.0'
                xmlns="http://www.w3.org/TR/xhtml1/transitional"
                exclude-result-prefixes="#default">

<xsl:import
	href="/usr/share/sgml/docbook/xsl-stylesheets/xhtml/onechunk.xsl"/>
<xsl:param name="chunk.section.depth" select="'1'"/>
<xsl:param name="html.ext" select="'.html'"/>
<xsl:param name="navig.graphics" select="'0'"/>
<xsl:param name="generate.chapter.toc" select="'1'"/>
<xsl:param name="toc.section.depth" select="'3'"/>
<xsl:param name="generate.toc">
        appendix  toc
        article   toc
        chapter   toc
        part      toc
        preface   toc
        qandadiv  toc
        qandaset  toc
        reference toc
        section   toc
        set       toc
</xsl:param>
<xsl:param name="section.autolabel" select="'1'"/>
<xsl:param name="chunker.output.encoding" select="'ISO-8859-1'"/>
<xsl:param name="section.autolabel" select="'1'"/>
<xsl:param name="section.label.includes.component.label" select="1"/>
<xsl:param name="html.stylesheet.type">text/css</xsl:param>
<xsl:param name="html.stylesheet">styleguibo.css</xsl:param>
<xsl:param name="css.decoration">1</xsl:param>
<xsl:param name="callout.defaultcolumn" select="'60'"/>
<xsl:param name="callout.graphics" select="'1'"/>
<xsl:param name="callout.list.table" select="'1'"/>
<xsl:param name="callout.graphics.path">images</xsl:param>
</xsl:stylesheet>
