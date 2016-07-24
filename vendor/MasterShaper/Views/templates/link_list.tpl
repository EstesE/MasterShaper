{if !isset($link_source)}
 {raise_error message="No valid link source provided!" stop=true}
{/if}
{if $link_source->hasModelLinks()}
 This target is assigned to the following objects:<br />
 {foreach from=$link_source->getModelLinkedList(true, true) item=obj name=objects}
  {if $obj->isHavingItems() && $obj->hasItems()}
   {$objects = $obj->getItems()}
  {elseif !$obj->isHavingItems()}
   {$objects = array($obj)}
  {/if}
  {foreach from=$objects item=link name=links}
   {if $link::getModelName(true) == 'TargetModel' || $link::getModelName(true) == 'AssignTargetToGroupsModel'}
 <a href="{get_url page='targets' mode='edit' id=$link->getSafeLink()}" title="Edit target {$link->getName()}"><img src="{$icon_targets}" alt="target icon" />&nbsp;{$link->getName()}</a>{if !isset($smarty.foreach.links.last) || empty($smarty.foreach.links.last)},{/if}
   {elseif $link::getModelName(true) == 'PipeModel'}
 <a href="{get_url page='pipes' mode='edit' id=$link->getSafeLink()}" title="Edit pipe {$link->getName()}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$link->getName()}</a>{if !isset($smarty.foreach.links.last) || empty($smarty.foreach.links.last)},{/if}
   {elseif $link::getModelName(true) == 'ChainModel'}
 <a href="{get_url page='chains' mode='edit' id=$link->getSafeLink()}" title="Edit chain {$link->getName()}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$link->getName()}</a>{if !isset($smarty.foreach.links.last) || empty($smarty.foreach.links.last)},{/if}
   {/if}
  {/foreach}
  {if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
