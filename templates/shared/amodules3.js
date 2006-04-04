// Support for expanders @ Lister

var expander_open=new Array();    
    /* we remember opened expanders so we know what to do when clicked */

function expander_is_open(name,id,button){
    /*
     * This would just return exander's state on a button click. If another expander is
     * open, it would not do anything, just return it's button's name.
     */
    id=name+"_"+id;
    if(expander_open[id]==button){
        expander_open[id]='';
        return "_closing";
    }else if(expander_open[id]){
        return expander_open[id];
    }else{
        expander_open[id]=button;
        return "_opening";
    }
}

function button_on(id){
    /*
     * Highlight expander button as it was pressed
     */
    button=document.getElementById(id);

    for(c=button.parentNode.firstChild;c;c=c.nextSibling){
        if(c==button)continue;
        if(!c.style)continue;
        c.className='expanded_other';
    }

    button.className='expanded_this';
}

function button_off(id){
    /*
     * highlight expander button as it was released
     */
    button=document.getElementById(id);

    for(c=button.parentNode.firstChild;c;c=c.nextSibling){
        if(c==button)continue;
        if(!c.style)continue;
        c.className='not_expanded';
    }

    button.className='not_expanded';
}

function expand(id,step){
    /*
     * This adds a bit of animation. It would "grow" div over time. Use this on a temporary div,
     * so that when it's gone from the page, expanding effect will cease itself
     */
    if(step>200)return;
    expander=document.getElementById(id);
    if(!expander)return;
    expander.style.paddingBottom=step+"px";
    setTimeout("expand('"+id+"',"+(30+step)+")",50);
}

function expander_flip(name,id,button,expander_url){
    /*
     * This opens / closes lister's expander. Use this function
     * under 'onclick' for your table row. It will take care of
     * adding additional row and loading content there. expander_url is
     * a prefix, which will have id appended
     */
    row=document.getElementById(name+"_"+id);

    expander_status = expander_is_open(name,id,button);
    if(expander_status=="_closing"){
        nextrow=row.nextSibling;
        row.parentNode.removeChild(nextrow);

        button_off(name+"_"+button+"_"+id);
    }else if(expander_status=="_opening"){

        tmp=row.parentNode.firstChild;
        if(!tmp.firstChild)tmp=tmp.nextSibling;
        header = tmp.firstChild;
        for(cs=1;header=header.nextSibling;cs++);

        newrow=document.createElement("tr");
        nextrow=row.nextSibling;
        if(!nextrow){
            row.parentNode.appendChild(newrow);
        }else{
            row.parentNode.insertBefore(newrow,nextrow);
        }
        my_td=document.createElement("td");
        my_td.colspan=cs;


        /* TODO: here is some template-dependant stuff. It's better to move this thing
         * out. Not sure if the temporary thing can me removed easily, but the background
         * color should be customizable. And it's even better to use class names, BTW!
         */
        bg="white";

        newrow.innerHTML='<td style="background: '+bg+'; border: 1px solid black; border-top: 0px; padding: 15px" colspan="'+cs+'" id="'+name+"_expandedcontent_"+id+'" ><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img src=amodules3/img/loading.gif></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table></td>';
        aasn(name+"_expandedcontent_"+id,expander_url+id);
        expand('autoexpander_'+id,0);

        button_on(name+"_"+button+"_"+id);
    }else{
        /* other button is active. Close that one and open new one */
        expander_flip(name,id,expander_status,expander_url);
        expander_flip(name,id,button,expander_url);
    }

}

/******* MISC FUNCTIONS *******/
function w(url,width,height){
    window.open(url,'','width='+width+',height='+height+',scrollbars=yes,resizable=yes');
}

function ajax_done(){
    /* This function is called last from Ajax class. If this function is not called, then the whole output might contain
     * errors
     */
}
