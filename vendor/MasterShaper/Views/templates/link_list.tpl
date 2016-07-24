{if !isset($link_source)}
 {raise_error message="No valid link source provided!" stop=true}
{/if}
{if $link_source->hasModelLinks()}
 This {if $link_source->hasModelFriendlyName()}{$link_source->getModelFriendlyName()}{else}object{/if} is linked to the following objects:<br />
 {foreach from=$link_source->getModelLinkedList(true, true) item=obj name=objects}
  {if $obj->isHavingItems() && $obj->hasItems()}
   {$objects = $obj->getItems()}
  {elseif !$obj->isHavingItems()}
   {$objects = array($obj)}
  {/if}
  {foreach from=$objects item=link name=links}
<a href="{get_url page='targets' mode='edit' id=$link->getSafeLink()}" title="Edit target {$link->getName()}"><img src="{if $link->hasModelIcon()}{${$link->getModelIcon()}}{else}{$icon_options}{/if}" alt="target icon" />&nbsp;{$link->getName()}</a>{if !isset($smarty.foreach.links.last) || empty($smarty.foreach.links.last)},{/if}
  {/foreach}
  {if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
