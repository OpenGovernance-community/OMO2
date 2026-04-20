<?php
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	   <xsl:template match="data/*">
         <xsl:if test="normalize-space(.) != ''">

          <div class='data_field' title='Inconnu'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="value" />
					</xsl:call-template>						                   
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="parent" />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/d5">
         <xsl:if test="normalize-space(.) != ''">

          <div class='data_field' title='Ma Raison d&apos;être 2'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="ancestor" />
					</xsl:call-template>
					
					
					
 <!-- On récupère l'ID du template (t) de l'ancêtre -->
      <xsl:variable name="templateId" select="../../t" />
<p>Template ID: <xsl:value-of select="$templateId" /></p>
      <!-- Sélection du d5 du template référencé -->
      <xsl:variable name="inheritedD5" select="//ID[text() = $templateId]/../data/d5/value" />
<p>D5 hérité: <xsl:value-of select="$inheritedD5" /></p>
      <!-- Affichage du texte hérité s'il existe -->
      <xsl:if test="normalize-space($inheritedD5) != ''">
        <div class="data_field_inherited" title="Raison d'être héritée">
          <div>
            <xsl:call-template name="replace-newline">
              <xsl:with-param name="text" select="$inheritedD5" />
            </xsl:call-template>
          </div>
        </div>
      </xsl:if>	
					
					
		<div>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="value" />
					</xsl:call-template>
					</div>
		</div>			    
						                   
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/d7">
         <xsl:if test="normalize-space(.) != ''">
          <div class='data_field' title='Domaines d&apos;autorité'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="value" />
					</xsl:call-template>						                   
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="parent" />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/d8">
         <xsl:if test="normalize-space(.) != ''">
          <div class='data_field' title='Stratégie'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="value" />
					</xsl:call-template>						                   
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="parent" />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>

	   <xsl:template match="data/d6">
         <xsl:if test="normalize-space(.) != ''">
          <div class='data_field' title='Attendus'>
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="value" />
					</xsl:call-template>						                   
		  <xsl:call-template name="replace-newline">
						<xsl:with-param name="text" select="parent" />
					</xsl:call-template>						                   
				
					
				</div>
		</xsl:if>
    </xsl:template>
    
</xsl:stylesheet>
