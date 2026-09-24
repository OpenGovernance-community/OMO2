(function(window,document){
'use strict';
if(window.omoChoiceChangeDetails)return;
var lifecycleTexts={notExisting:'Cet élément n’existait pas.',deleted:'Supprimé'};
var detailTexts={name:'Nom',fullName:'Nom complet',color:'Couleur',icon:'Icône',adminMin:'Minimum d’administrateurs',adminMax:'Maximum d’administrateurs',property:'Propriété',introduction:'introduction',conclusion:'conclusion',title:'titre',detail:'détail',description:'Description',person:'Personne en charge',sourceType:'Type de source',sourceLink:'Lien vers la source',sourceDocument:'Document source',updateFrequency:'Fréquence de mise à jour',expectedMoment:'Moment attendu',ethercalcSync:'Fréquence de synchronisation EtherCalc',spreadsheetSync:'Fréquence de synchronisation du tableur',ethercalcCell:'Cellule EtherCalc',ethercalcRange:'Plage EtherCalc',ethercalcDateColumn:'Colonne des dates EtherCalc',ethercalcValueColumn:'Colonne des valeurs EtherCalc',spreadsheetSheet:'Feuille du tableur',spreadsheetCell:'Cellule du tableur',spreadsheetRange:'Plage du tableur',spreadsheetDateColumn:'Colonne des dates du tableur',spreadsheetValueColumn:'Colonne des valeurs du tableur',chartMinimum:'Valeur basse du graphique',ceiling:'Plafond',referenceType:'Type de référence',referenceScale:'Échelle de référence',showCumulative:'Afficher le cumul',referencePoint:'Point de référence',yes:'Oui',no:'Non',personFallback:'Personne #',sourceManual:'Manuelle',sourceEthercalcCell:'Cellule EtherCalc',sourceEthercalcTable:'Tableau EtherCalc',sourceSpreadsheetCell:'Cellule tableur',sourceSpreadsheetTable:'Tableau tableur',frequencyHourly:'Chaque heure',frequencyDaily:'Chaque jour',frequencyWeekly:'Chaque semaine',frequencyMonthly:'Chaque mois',frequencyQuarterly:'Chaque trimestre',frequencySemiannual:'Chaque semestre',frequencyYearly:'Chaque année'};
if(typeof window.fetch==='function')window.fetch('/common/jstranslation/change_details.php',{credentials:'same-origin'}).then(function(response){if(!response.ok)throw new Error('Translation unavailable');return response.json();}).then(function(texts){[lifecycleTexts,detailTexts].forEach(function(target){Object.keys(target).forEach(function(key){if(typeof texts[key]==='string')target[key]=texts[key];});});document.querySelectorAll('[data-omo-change-details-container]').forEach(function(node){delete node.dataset.omoChangeDetailsBound;});hydrate(document);document.querySelectorAll('[data-change-lifecycle-text]').forEach(function(node){node.textContent=lifecycleTexts[node.dataset.changeLifecycleText];});}).catch(function(){});
function sanitize(value){value=String(value||'');if(window.omoSimpleHtmlField&&typeof window.omoSimpleHtmlField.sanitizeHtml==='function')return window.omoSimpleHtmlField.sanitizeHtml(value);var node=document.createElement('div');node.textContent=value;return node.innerHTML.replace(/\r\n?|\n/g,'<br>');}
function richRoot(value){var root=document.createElement('div');root.innerHTML=sanitize(value);return root;}
function richTokens(root){var tokens=[],blocks={P:1,LI:1,UL:1,OL:1,BLOCKQUOTE:1,H1:1,H2:1,H3:1};function boundary(){if(tokens.length&&tokens[tokens.length-1].compareValue!=='\n')tokens.push({compareValue:'\n',node:null,start:0,end:0});}function visit(node){if(node.nodeType===3){var value=String(node.nodeValue||''),matcher=/(\s+|[^\s]+)/g,match;while((match=matcher.exec(value))!==null)tokens.push({compareValue:/^\s+$/.test(match[0])?' ':match[0],node:node,start:match.index,end:match.index+match[0].length});return;}if(node.nodeType!==1)return;var block=blocks[String(node.tagName||'').toUpperCase()]===1;if(block)boundary();Array.prototype.forEach.call(node.childNodes||[],visit);if(block)boundary();}Array.prototype.forEach.call(root.childNodes||[],visit);while(tokens.length&&!tokens[tokens.length-1].node)tokens.pop();return tokens;}
function highlight(tokens,indexes,className){var groups=[];tokens.forEach(function(token,index){if(!token.node)return;var group=groups.find(function(item){return item.node===token.node;});if(!group){group={node:token.node,tokens:[]};groups.push(group);}group.tokens.push({token:token,changed:indexes[String(index)]===true});});groups.forEach(function(group){var source=String(group.node.nodeValue||''),fragment=document.createDocumentFragment(),cursor=0,active=null;function append(value,changed){if(!value)return;if(changed){if(!active){active=document.createElement('span');active.className=className;fragment.appendChild(active);}active.appendChild(document.createTextNode(value));}else{active=null;fragment.appendChild(document.createTextNode(value));}}group.tokens.forEach(function(item){append(source.slice(cursor,item.token.start),false);append(source.slice(item.token.start,item.token.end),item.changed);cursor=item.token.end;});append(source.slice(cursor),false);group.node.parentNode.replaceChild(fragment,group.node);});}
function richDiff(before,after){var beforeRoot=richRoot(before),afterRoot=richRoot(after),beforeTokens=richTokens(beforeRoot),afterTokens=richTokens(afterRoot),operations=window.omoChoiceWordDiff?window.omoChoiceWordDiff.buildOperations(beforeTokens.map(function(t){return t.compareValue;}),afterTokens.map(function(t){return t.compareValue;}),true):null,removed={},added={};(operations||[]).forEach(function(op){if(op.type==='removed')removed[String(op.beforeIndex)]=true;if(op.type==='added')added[String(op.afterIndex)]=true;});highlight(beforeTokens,removed,'omo-change-details__removed');highlight(afterTokens,added,'omo-change-details__added');return{before:beforeRoot,after:afterRoot};}
function valueBlock(label,value,side,operations,rich,isList){var block=document.createElement('div'),heading=document.createElement('span'),content=document.createElement('div');block.className='omo-change-details__value is-'+side;heading.className='omo-change-details__value-label';heading.textContent=label;content.className='omo-change-details__value-content';block.appendChild(heading);block.appendChild(content);if(isList&&Array.isArray(value)&&value.length){var ul=document.createElement('ul');ul.className='omo-change-details__items';value.forEach(function(item){var li=document.createElement('li');li.textContent=String(item==null?'':item);ul.appendChild(li);});content.appendChild(ul);content.classList.add(side==='before'?'is-removed':'is-added');}else if(rich){content.classList.add('omo-change-details__value-content--rich','is-'+side);while(rich.firstChild)content.appendChild(rich.firstChild);}else if(Array.isArray(operations)){operations.forEach(function(op){if(op.type==='equal'){content.appendChild(document.createTextNode(op.value));return;}if((side==='before'&&op.type==='removed')||(side==='after'&&op.type==='added')){var span=document.createElement('span');span.className=op.type==='added'?'omo-change-details__added':'omo-change-details__removed';span.textContent=op.value;content.appendChild(span);}});}else{content.textContent=String(value||'');if(value)content.classList.add(side==='before'?'is-removed':'is-added');}if(!String(content.textContent||'').trim()){content.textContent='(vide)';content.classList.add('is-empty');}return block;}
function operationTitle(label,status){label=String(label||'Modification');if(/^(Ajout|Suppression|Modification) de /i.test(label))return label;if(status==='added')return'Ajout de '+label;if(status==='removed')return'Suppression de '+label;return'Modification de '+label;}
function listItemSimilarity(before,after){var beforeWords=String(before||'').toLowerCase().match(/[a-z0-9\u00c0-\u017f]+/g)||[],afterWords=String(after||'').toLowerCase().match(/[a-z0-9\u00c0-\u017f]+/g)||[],words={},common=0;beforeWords.forEach(function(word){words[word]=1;});afterWords.forEach(function(word){if(words[word]===1)common++;words[word]=2;});return Object.keys(words).length?common/Object.keys(words).length:0;}
function diffList(label,beforeItems,afterItems){beforeItems=Array.isArray(beforeItems)?beforeItems.map(String):[];afterItems=Array.isArray(afterItems)?afterItems.map(String):[];var changes=[],remainingAfter=afterItems.slice(),removed=[];beforeItems.forEach(function(beforeItem){var exactIndex=remainingAfter.indexOf(beforeItem);if(exactIndex>=0)remainingAfter.splice(exactIndex,1);else removed.push(beforeItem);});var modifiedBefore={},modifiedAfter={};removed.forEach(function(beforeItem,beforeIndex){var bestIndex=-1,bestScore=0;remainingAfter.forEach(function(afterItem,afterIndex){var score=listItemSimilarity(beforeItem,afterItem);if(!modifiedAfter[afterIndex]&&score>bestScore){bestScore=score;bestIndex=afterIndex;}});if(bestIndex>=0&&bestScore>=.35){modifiedBefore[beforeIndex]=true;modifiedAfter[bestIndex]=true;changes.push({label:label,before:beforeItem,after:remainingAfter[bestIndex],status:'changed'});}});var onlyRemoved=removed.filter(function(item,index){return!modifiedBefore[index];}),onlyAdded=remainingAfter.filter(function(item,index){return!modifiedAfter[index];});if(onlyRemoved.length)changes.push({label:label,before:onlyRemoved,after:[],status:'removed',list:true});if(onlyAdded.length)changes.push({label:label,before:[],after:onlyAdded,status:'added',list:true});return changes;}
function governanceChanges(action,authorities){action=action||{};authorities=Array.isArray(authorities)?authorities:[];var before=action.before||{},after=action.after||{},changes=[],actionType=String(action.type||''),isRule=actionType.indexOf('rule.')===0,isProject=actionType.indexOf('project.')===0,isRecurring=actionType.indexOf('recurring_task.')===0,isIndicator=actionType.indexOf('indicator.')===0,fields=isRule?{title:'Titre',intention:'Intention',description:'R\u00e8gle',review_date:'Requestionnement',expiration_date:'Ech\u00e9ance'}:(isProject?{title:'Titre',description:'Description',status:'Statut',project_size:'Taille',planned_start_date:'D\u00e9but planifi\u00e9',planned_end_date:'Fin planifi\u00e9e',priority:'Priorit\u00e9',importance:'Importance strat\u00e9gique'}:(isRecurring?{title:'Titre',description:'Description',frequency:'Fr\u00e9quence',schedule:'Moment attendu',display_lead_value:'Affichage en avance',display_lead_unit:'Unit\u00e9 d anticipation',execution_duration_value:'D\u00e9lai avant retard',execution_duration_unit:'Unit\u00e9 du d\u00e9lai'}:(isIndicator?{name:'Nom',description:'Description',source_url:'Lien vers la source',measurement_frequency:'Fr\u00e9quence de mesure',measurement_schedule:'Moment attendu',chart_min_value:'Valeur basse du graphique',reference_type:'Type de r\u00e9f\u00e9rence',show_cumulative:'Afficher le cumul'}:{name:'Nom',full_name:'Nom complet',color:'Couleur'})));function strip(value){return String(value||'').replace(/<[^>]*>/g,'').trim();}function push(label,oldValue,newValue,options){options=options||{};var oldEmpty=Array.isArray(oldValue)?oldValue.length===0:String(oldValue||'')==='',newEmpty=Array.isArray(newValue)?newValue.length===0:String(newValue||'')==='',status=options.status||(oldEmpty?'added':(newEmpty?'removed':'changed'));changes.push({label:label,before:oldValue,after:newValue,status:status,rich:!!options.rich,list:!!options.list});}function propertyMap(properties){var result={};(Array.isArray(properties)?properties:[]).forEach(function(property,index){var name=String(property.name||property.shortname||'').trim().toLowerCase(),key=name?'name:'+name:(Number(property.id||0)>0?'id:'+Number(property.id):'index:'+index);result[key]=property;});return result;}function propertyValue(property,preserveHtml){if(!property)return'';var value=String(property.listItemType||'')==='authority'&&property.displayValue!=null?property.displayValue:property.value;if(value==null)return'';if(typeof value==='object')value=JSON.stringify(value);return preserveHtml?String(value):strip(value);}function listItems(property,formatId){if(!property)return null;var source=property.value,raw=typeof source==='string'?source.trim():'',decoded=null;try{decoded=Array.isArray(source)||(source&&typeof source==='object')?source:(raw===''?[]:JSON.parse(raw));}catch(error){decoded=raw===''?[]:[raw];}var hasList=Array.isArray(decoded)||(decoded&&typeof decoded==='object'&&Array.isArray(decoded.items));if([2,7].indexOf(Number(formatId||property.formatId||0))===-1&&!hasList)return null;var items=decoded&&!Array.isArray(decoded)&&typeof decoded==='object'&&Array.isArray(decoded.items)?decoded.items:decoded;if(!Array.isArray(items))items=items==null||String(items).trim()===''?[]:[items];var display=String(property.displayValue||'').split(';').map(function(item){return item.trim();}).filter(Boolean),displayIndex=0;return items.filter(function(item){return!(item&&typeof item==='object'&&item.delete===true);}).map(function(item){var fallback=display[displayIndex++]||'';if(item&&typeof item==='object'){var title=String(item.label||item.title||item.value||fallback||'').trim(),description=String(item.description||item.text||'').trim();return title&&description?title+' - '+description:(title||description||JSON.stringify(item));}return fallback||String(item==null?'':item).trim();}).filter(Boolean);}function structured(property){if(!property||[6,7].indexOf(Number(property.formatId||0))===-1)return{};try{var parsed=JSON.parse(String(property.value||''));return parsed&&!Array.isArray(parsed)&&typeof parsed==='object'?parsed:{};}catch(error){return{};}}if(isRule){var authorityLabel=function(state){var id=Number(state.IDauthority||0),match=authorities.find(function(authority){return Number(authority.id||0)===id;});return id>0?(match?String(match.label||''):'Autorit\u00e9 '+id):'Holon courant';},beforeScope=Object.keys(before).length?authorityLabel(before):'',afterScope=Object.keys(after).length?authorityLabel(after):'';if(beforeScope!==afterScope)push('Port\u00e9e',beforeScope,afterScope);}Object.keys(fields).forEach(function(key){var oldValue=String(before[key]||''),newValue=String(after[key]||'');if(oldValue!==newValue)push(fields[key],oldValue,newValue,{rich:(isRule&&(key==='intention'||key==='description'))||((isProject||isRecurring)&&key==='description')});});if(!isRule&&!isProject&&!isRecurring&&!isIndicator){var beforeProperties=propertyMap(before.editor_payload&&before.editor_payload.properties),afterProperties=propertyMap(after.editor_payload&&after.editor_payload.properties);Object.keys(beforeProperties).concat(Object.keys(afterProperties)).filter(function(key,index,all){return all.indexOf(key)===index;}).forEach(function(key){var beforeProperty=beforeProperties[key]||null,afterProperty=afterProperties[key]||null,property=afterProperty||beforeProperty||{},label=String(property.name||property.shortname||'Propri\u00e9t\u00e9'),formatId=Number(property.formatId||0),beforeItems=listItems(beforeProperty,formatId),afterItems=listItems(afterProperty,formatId);if(beforeItems!==null||afterItems!==null){Array.prototype.push.apply(changes,diffList(label,beforeItems||[],afterItems||[]));return;}if(formatId===7){var beforeComposite=structured(beforeProperty),afterComposite=structured(afterProperty);if(String(beforeComposite.before||'')!==String(afterComposite.before||''))push(label+' - introduction',String(beforeComposite.before||''),String(afterComposite.before||''),{rich:true});if(String(beforeComposite.after||'')!==String(afterComposite.after||''))push(label+' - conclusion',String(beforeComposite.after||''),String(afterComposite.after||''),{rich:true});return;}if(formatId===6){var beforeText=structured(beforeProperty),afterText=structured(afterProperty);if(String(beforeText.text||'')!==String(afterText.text||''))push(label+' - titre',String(beforeText.text||''),String(afterText.text||''));if(String(beforeText.detail||'')!==String(afterText.detail||''))push(label+' - detail',String(beforeText.detail||''),String(afterText.detail||''),{rich:true});return;}var rich=Number(property.formatId||0)===5,oldValue=propertyValue(beforeProperty,rich),newValue=propertyValue(afterProperty,rich);if(oldValue!==newValue)push(label,oldValue,newValue,{rich:rich});});}return changes;}
function displayChange(changes,label,before,after,rich){
    var oldValue=String(before==null?'':before),newValue=String(after==null?'':after);
    if(oldValue===newValue)return;
    changes.push({label:label,before:oldValue,after:newValue,status:oldValue===''?'added':(newValue===''?'removed':'changed'),rich:!!rich});
}
function holonPropertyMap(properties){
    var result={};
    (Array.isArray(properties)?properties:[]).forEach(function(property,index){
        var name=String(property&&property.name||property&&property.shortname||'').trim().toLowerCase();
        var key=name?'name:'+name:(Number(property&&property.id||0)>0?'id:'+Number(property.id):'index:'+index);
        result[key]=property;
    });
    return result;
}
function holonPropertyItems(property){
    if(!property)return[];
    if(Array.isArray(property.displayItems))return property.displayItems.map(function(item){
        if(item&&typeof item==='object'){
            var title=String(item.label||item.title||item.value||''),description=String(item.description||item.text||'');
            return title&&description?title+' - '+description:(title||description);
        }
        return String(item==null?'':item);
    }).filter(Boolean);
    var raw=property.value,decoded;
    try{decoded=typeof raw==='string'?JSON.parse(raw):raw;}catch(error){decoded=String(raw||'').split(/\r\n|\r|\n|\|/);}
    var items=Array.isArray(decoded)?decoded:(decoded&&Array.isArray(decoded.items)?decoded.items:[]);
    return items.filter(function(item){return!(item&&typeof item==='object'&&item.delete===true);}).map(function(item){
        if(item&&typeof item==='object'){
            var title=String(item.label||item.title||item.value||''),description=String(item.description||item.text||'');
            return (title&&description?title+' - '+description:(title||description)).trim();
        }
        return String(item==null?'':item).trim();
    }).filter(Boolean);
}
function detailedHolonChanges(action){
    var before=action.before||{},after=action.after||{},changes=[];
    [['name',detailTexts.name],['full_name',detailTexts.fullName],['color',detailTexts.color]].forEach(function(field){
        displayChange(changes,field[1],before[field[0]],after[field[0]]);
    });
    var beforePayload=before.editor_payload||{},afterPayload=after.editor_payload||{};
    [['icon',detailTexts.icon],['adminMin',detailTexts.adminMin],['adminMax',detailTexts.adminMax]].forEach(function(field){
        displayChange(changes,field[1],beforePayload[field[0]],afterPayload[field[0]]);
    });
    var oldProperties=holonPropertyMap(beforePayload.properties),newProperties=holonPropertyMap(afterPayload.properties);
    Object.keys(oldProperties).concat(Object.keys(newProperties)).filter(function(key,index,all){return all.indexOf(key)===index;}).forEach(function(key){
        var oldProperty=oldProperties[key]||null,newProperty=newProperties[key]||null,property=newProperty||oldProperty||{};
        var label=String(property.name||property.shortname||detailTexts.property),formatId=Number(property.formatId||0);
        if(formatId===2||formatId===7){
            Array.prototype.push.apply(changes,diffList(label,holonPropertyItems(oldProperty),holonPropertyItems(newProperty)));
            if(formatId===7){
                var oldParts={},newParts={};
                try{oldParts=JSON.parse(String(oldProperty&&oldProperty.value||''))||{};}catch(error){}
                try{newParts=JSON.parse(String(newProperty&&newProperty.value||''))||{};}catch(error){}
                displayChange(changes,label+' - '+detailTexts.introduction,oldParts.before,newParts.before,true);
                displayChange(changes,label+' - '+detailTexts.conclusion,oldParts.after,newParts.after,true);
            }
            return;
        }
        if(formatId===6){
            var oldText={},newText={};
            try{oldText=JSON.parse(String(oldProperty&&oldProperty.value||''))||{};}catch(error){}
            try{newText=JSON.parse(String(newProperty&&newProperty.value||''))||{};}catch(error){}
            displayChange(changes,label+' - '+detailTexts.title,oldText.text,newText.text);
            displayChange(changes,label+' - '+detailTexts.detail,oldText.detail,newText.detail,true);
            return;
        }
        displayChange(changes,label,oldProperty&&(oldProperty.displayValue!=null?oldProperty.displayValue:oldProperty.value),newProperty&&(newProperty.displayValue!=null?newProperty.displayValue:newProperty.value),formatId===5);
    });
    return changes;
}
function detailedIndicatorChanges(action,responsibleLabels){
    var before=action.before||{},after=action.after||{},changes=[];
    var frequencies={hourly:detailTexts.frequencyHourly,daily:detailTexts.frequencyDaily,weekly:detailTexts.frequencyWeekly,monthly:detailTexts.frequencyMonthly,quarterly:detailTexts.frequencyQuarterly,semiannual:detailTexts.frequencySemiannual,yearly:detailTexts.frequencyYearly};
    var sources={manual:detailTexts.sourceManual,ethercalc_cell:detailTexts.sourceEthercalcCell,ethercalc_table:detailTexts.sourceEthercalcTable,spreadsheet_cell:detailTexts.sourceSpreadsheetCell,spreadsheet_table:detailTexts.sourceSpreadsheetTable};
    function frequency(value){return frequencies[value]||value||'';}
    function person(value){var id=Number(value||0);return id>0?String(responsibleLabels[id]||detailTexts.personFallback+id):'';}
    displayChange(changes,detailTexts.name,before.name,after.name);
    displayChange(changes,detailTexts.description,before.description,after.description);
    displayChange(changes,detailTexts.person,person(before.IDuser_responsible),person(after.IDuser_responsible));
    displayChange(changes,detailTexts.sourceType,sources[before.source_type]||before.source_type,sources[after.source_type]||after.source_type);
    displayChange(changes,detailTexts.sourceLink,before.source_url,after.source_url);
    displayChange(changes,detailTexts.sourceDocument,before.IDdocument,after.IDdocument);
    displayChange(changes,detailTexts.updateFrequency,frequency(before.measurement_frequency),frequency(after.measurement_frequency));
    displayChange(changes,detailTexts.expectedMoment,before.measurement_schedule,after.measurement_schedule);
    displayChange(changes,detailTexts.ethercalcSync,frequency(before.ethercalc_frequency),frequency(after.ethercalc_frequency));
    displayChange(changes,detailTexts.spreadsheetSync,frequency(before.spreadsheet_frequency),frequency(after.spreadsheet_frequency));
    [['ethercalc_cell',detailTexts.ethercalcCell],['ethercalc_range',detailTexts.ethercalcRange],['ethercalc_date_column',detailTexts.ethercalcDateColumn],['ethercalc_value_column',detailTexts.ethercalcValueColumn],['spreadsheet_sheet',detailTexts.spreadsheetSheet],['spreadsheet_cell',detailTexts.spreadsheetCell],['spreadsheet_range',detailTexts.spreadsheetRange],['spreadsheet_date_column',detailTexts.spreadsheetDateColumn],['spreadsheet_value_column',detailTexts.spreadsheetValueColumn],['chart_min_value',detailTexts.chartMinimum],['ceiling_value',detailTexts.ceiling]].forEach(function(field){
        displayChange(changes,field[1],before[field[0]],after[field[0]]);
    });
    displayChange(changes,detailTexts.referenceType,before.reference_type,after.reference_type);
    displayChange(changes,detailTexts.referenceScale,before.reference_scale,after.reference_scale);
    displayChange(changes,detailTexts.showCumulative,before.show_cumulative?detailTexts.yes:detailTexts.no,after.show_cumulative?detailTexts.yes:detailTexts.no);
    function points(state){return(Array.isArray(state.reference_points)?state.reference_points:[]).map(function(point){return String(point.position_percent)+' % : '+String(point.value)+(point.point_at?' ('+String(point.point_at)+')':'');});}
    Array.prototype.push.apply(changes,diffList(detailTexts.referencePoint,points(before),points(after)));
    return changes;
}
function detailedGovernanceChanges(action,authorities,responsibleLabels){
    var type=String(action&&action.type||'');
    if(type.indexOf('holon.')===0)return detailedHolonChanges(action||{});
    if(type.indexOf('indicator.')===0)return detailedIndicatorChanges(action||{},responsibleLabels||{});
    return governanceChanges(action,authorities);
}
function operationOf(action) {
    var operation=String(action&&action.type||'').split('.').pop();
    return operation==='create'||operation==='delete'?operation:'update';
}
function createLifecycleList(changes,operation) {
    var comparison=document.createElement('div'),notice=document.createElement('div'),values=document.createElement('div');
    comparison.className='omo-change-details__lifecycle';
    notice.className='omo-change-details__absence generic-soft-panel';
    notice.dataset.changeLifecycleText=operation==='create'?'notExisting':'deleted';
    notice.textContent=lifecycleTexts[notice.dataset.changeLifecycleText];
    values.className='omo-change-details__list';
    changes.forEach(function(change) {
        var raw=operation==='create'?change.after:change.before;
        if(raw==null||(Array.isArray(raw)?raw.length===0:String(raw)===''))return;
        var card=document.createElement('section'),title=document.createElement('strong');
        card.className='omo-change-details__card omo-change-details__card--snapshot';
        title.className='omo-change-details__title';title.textContent=String(change.label||'');
        card.appendChild(title);
        var value=valueBlock('',raw,operation==='create'?'after':'before',null,change.rich?richRoot(raw):null,!!change.list);
        value.className='omo-change-details__value';
        value.querySelector('.omo-change-details__value-label').remove();
        var content=value.querySelector('.omo-change-details__value-content');
        content.classList.remove('is-added','is-removed','is-before','is-after');
        card.appendChild(value);values.appendChild(card);
    });
    if(operation==='create'){comparison.appendChild(notice);comparison.appendChild(values);}
    else{comparison.appendChild(values);comparison.appendChild(notice);}
    return comparison;
}
function createList(changes,options){options=options||{};changes=Array.isArray(changes)?changes:[];if(options.operation==='create'||options.operation==='delete')return createLifecycleList(changes,options.operation);var list=document.createElement('div');list.className='omo-change-details__list';changes.forEach(function(change){var isList=!!change.list,beforeRaw=isList&&Array.isArray(change.before)?change.before:(change.before==null?'':String(change.before)),afterRaw=isList&&Array.isArray(change.after)?change.after:(change.after==null?'':String(change.after)),before=isList?beforeRaw.join('\n'):beforeRaw,after=isList?afterRaw.join('\n'):afterRaw,status=String(change.status||'changed'),card=document.createElement('section'),title=document.createElement('strong'),operations=null,rich=null;if(['added','removed','changed'].indexOf(status)===-1)status='changed';card.className='omo-change-details__card omo-change-details__card--'+status;title.className='omo-change-details__title';title.textContent=change.operationLabel===false?String(change.label||'Modification'):operationTitle(change.label,status);card.appendChild(title);if(change.rich)rich=richDiff(before,after);else if(!isList&&window.omoChoiceWordDiff)operations=window.omoChoiceWordDiff.buildOperations(before,after);card.appendChild(valueBlock('Avant',beforeRaw,'before',operations,rich?rich.before:null,isList));card.appendChild(valueBlock('Après',afterRaw,'after',operations,rich?rich.after:null,isList));list.appendChild(card);});return list;}
function create(changes,options){options=options||{};if(options.details===false)return createList(changes,options);var details=document.createElement('details'),summary=document.createElement('summary');details.className='omo-change-details';summary.textContent=String(options.label||'Détail');details.appendChild(summary);details.appendChild(createList(changes,options));return details;}
function payload(value){try{var binary=window.atob(String(value||'')),bytes=Uint8Array.from(binary,function(character){return character.charCodeAt(0);}),text=typeof TextDecoder==='function'?new TextDecoder().decode(bytes):decodeURIComponent(Array.prototype.map.call(bytes,function(byte){return '%'+('00'+byte.toString(16)).slice(-2);}).join(''));return JSON.parse(text);}catch(error){return null;}}
function hydrate(root){
    root=root||document;
    var selector='[data-omo-change-details-payload]',nodes=Array.prototype.slice.call(root.querySelectorAll(selector));
    if(root.matches&&root.matches(selector))nodes.unshift(root);
    nodes.forEach(function(details){
        var container=details.querySelector('[data-omo-change-details-container]');
        if(!container||container.dataset.omoChangeDetailsBound===details.dataset.omoChangeDetailsPayload)return;
        var data=payload(details.dataset.omoChangeDetailsPayload);
        var changes=data&&Array.isArray(data.changes)?data.changes:(data&&data.governanceAction?detailedGovernanceChanges(data.governanceAction,data.authorities,data.responsibleLabels):null);
        if(!Array.isArray(changes))return;
        container.replaceChildren(createList(changes,{operation:data.governanceAction?operationOf(data.governanceAction):data.operation}));
        container.dataset.omoChangeDetailsBound=details.dataset.omoChangeDetailsPayload;
    });
}
window.omoChoiceChangeDetails={create:create,createList:createList,diffList:diffList,governanceChanges:detailedGovernanceChanges,hydrate:hydrate};
function start(){
    hydrate(document);
    new MutationObserver(function(records){
        records.forEach(function(record){
            if(record.type==='attributes'){hydrate(record.target);return;}
            record.addedNodes.forEach(function(node){if(node.nodeType===1)hydrate(node);});
        });
    }).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:['data-omo-change-details-payload']});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})(window,document);
