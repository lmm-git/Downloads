{ajaxheader modname='Downloads' ui=true}
{pagesetvar name='title' value=$title}
{''|notifyfilters:'downloads.filter_hooks.users_extrahead'}
<h2>{$title}</h2>
{if !empty($subtitle)}
    <h3>{$subtitle}</h3>
{/if}

{insert name="getstatusmsg"}
{modulelinks type='User'}
