<div class="ui buttons">
{if isset($submit) && $submit}
 <button class="ui labeled icon positive button save" type="submit">
  <div class="ui inverted dimmer">
   <div class="ui loader"></div>
  </div>
  <i class="save icon"></i>Save
 </button>
{/if}
{if isset($discard) && $discard}
 <div class="or"></div>
 <button class="ui button discard">
  <i class="remove icon"></i>Discard
 </button>
{/if}
{if isset($reset) && $reset}
 <div class="or"></div>
 <button class="ui button reset">
  <i class="remove icon"></i>Reset
 </button>
{/if}
</div
