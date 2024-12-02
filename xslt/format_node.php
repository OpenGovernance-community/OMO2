<?php
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	   <xsl:template match="data/rde">
         <xsl:if test="normalize-space(.) != ''">

          <div class='data_field' title='Raison d&apos;être:'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="." />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/domain">
         <xsl:if test="normalize-space(.) != ''">
          <div class='data_field' title='Domaines d&apos;autorité'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="." />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/redevability">
         <xsl:if test="normalize-space(.) != ''">
          <div class='data_field' title='Attendus'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="." />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>
    
</xsl:stylesheet>
