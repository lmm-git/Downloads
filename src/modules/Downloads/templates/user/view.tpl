{ajaxheader modname='Downloads' ui=true}
<h3>{gt text='Download Items'}</h3>

{insert name="getstatusmsg"}
<form class="z-form" action="{modurl modname='Downloads' type='user' func='view'}" method="post" enctype="application/x-www-form-urlencoded">
    <fieldset{if $filter_active} class='filteractive'{/if}>
        {if $filter_active}{gt text='active' assign='filteractive'}{else}{gt text='inactive' assign='filteractive'}{/if}
        <legend>{gt text='Filter %1$s, %2$s item listed' plural='Filter %1$s, %2$s items listed' count=$rowcount tag1=$filteractive tag2=$rowcount}</legend>
        <input type="hidden" name="startnum" value="{$startnum}" />
        <input type="hidden" name="orderby" value="{$orderby}" />
        <input type="hidden" name="sdir" value="{$sdir}" />
        <div id="pages_multicategory_filter">
            <span id='categoryfilter'>
                <select id='category' name='category'>
                    {$catselectoptions}
                </select>
            </span>
            <span class="z-nowrap z-buttons">
                <input class='z-bt-filter' name="submit" type="submit" value="{gt text='Filter'}" />
                <a href="{modurl modname="Downloads" type='user' func='view'}" title="{gt text="Clear"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Clear" __title="Clear"} {gt text="Clear"}</a>
            </span>
        </div>
    </fieldset>
</form>
<table class="z-datatable">
    <thead>
        <tr>
            <td><a class='{$sort.class.title}' href='{$sort.url.title|safetext}'>{gt text='Title'}</a></td>
            <td>{gt text='Version'}</td>
            <td><a class='{$sort.class.hits}' href='{$sort.url.hits|safetext}'>{gt text='Downloads'}</a></td>
            <td>{gt text='Description'}</td>
            <td><a class='{$sort.class.submitter}' href='{$sort.url.submitter|safetext}'>{gt text='Submitter'}</a></td>
            <td>{gt text='Categories'}</td>
            <td>{gt text='Actions'}</td>
        </tr>
    </thead>
    <tbody>
        {foreach from=$downloads item='d'}
        <tr class="{cycle values="z-odd,z-even"}">
            <td>{$d.title|safetext}</td>
            <td>{$d.version|safetext}</td>
            <td>{$d.hits|safetext}</td>
            <td>{$d.description|safetext}</td>
            <td>{$d.submitter|safetext}</td>
            <td>{$d.category.title|safetext}</td>
            <td>
                <a href="{modurl modname="Downloads" type="user" func="prepHandOut" lid=$d.lid}">{img modname='core' set='icons/extrasmall' src='download.png' __title='Download' __alt='Download' class='tooltips'}</a>
            </td>
        </tr>
        {foreachelse}
        <tr><td colspan='6' class='z-center'>{gt text='No records in category "%1$s". Try a sub-category, or a different category.' tag1=$cid|getcategorynamefromid}</td></tr>
        {/foreach}
    </tbody>
</table>
{pager rowcount=$rowcount limit=$modvars.Downloads.perpage posvar='startnum'}
<script type="text/javascript">
// <![CDATA[
    Zikula.UI.Tooltips($$('.tooltips'));
// ]]>
</script>