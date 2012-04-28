/*!
 * Copyright (c) 2011-2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
if(myatu_bgm===undefined){var myatu_bgm={}}(function(b){b.extend(myatu_bgm,{SetTimer:function(){if(background_manager_vars.change_freq<=0){return}if(myatu_bgm.timer){clearTimeout(myatu_bgm.timer)}myatu_bgm.timer=setTimeout("myatu_bgm.SwitchBackground()",background_manager_vars.change_freq*1000)},OnBackgroundClick:function(a){if(a.target==this||b(a.target).hasClass("myatu_bgm_fs")){window.open(a.data.url);return false}},OnBackgroundHover:function(a){b(this).css("cursor",(a.target==this||b(a.target).hasClass("myatu_bgm_fs"))?"pointer":"auto")},SetBackgroundLink:function(a){var e=b("body");e.unbind("click",myatu_bgm.OnBackgroundClick).unbind("mouseover",myatu_bgm.OnBackgroundHover).css("cursor","auto");if(a!=""&&a!="#"){e.bind("click",{url:a},myatu_bgm.OnBackgroundClick).bind("mouseover",myatu_bgm.OnBackgroundHover)}},UrlReplaceQueryArgVal:function(g,f,a){var h=new RegExp("(?![?&])"+f+"=(.*?(?=\\?|\\&(?!amp;)|#|$))","ig");return g.replace(h,f+"="+encodeURIComponent(a))},AdjustImageSize:function(){var o=b("#myatu_bgm_top"),p=(background_manager_vars.fs_center=="true"),n={left:0,top:0},j=b(window).height(),a=win_width=b(window).width(),k,m,l;if(background_manager_vars.is_fullsize=="false"||background_manager_vars.fs_adjust=="false"){return}o.css({width:"",height:""});m=o.width()/o.height();k=a/m;if(k>=j){l=(k-j)/2;if(p){b.extend(n,{top:"-"+l+"px"})}}else{k=j;a=k*m;l=(a-win_width)/2;if(p){b.extend(n,{left:"-"+l+"px"})}}o.width(a).height(k).css(n)},AnimationCompleted:function(){b("#myatu_bgm_prev").remove();myatu_bgm.SetTimer();b(document).trigger("myatu_bgm_finish_transition")},AnimateSlide:function(l,r,a){var m=b("#myatu_bgm_top"),q=b("#myatu_bgm_prev"),o=m.offset(),t=q.offset(),p={},n,s;switch(l){case"top":s="top";p[s]=o.top+"px";pos=m.height()-t.top;n="-"+pos+"px";break;case"bottom":s="top";p[s]=o.top+"px";pos=q.height()+t.top;n=pos+"px";break;case"left":s="left";p[s]=o.left+"px";pos=m.width()-t.left;n="-"+pos+"px";break;case"right":s="left";p[s]=o.left+"px";pos=q.width()+t.left;n=pos+"px";break}m.css(s,n);m.css("visibility","");if(a===undefined||a===false){m.animate(p,{duration:r,complete:myatu_bgm.AnimationCompleted,step:function(d,c){switch(l){case"top":q.css(s,(m.height()+d)+"px");break;case"bottom":q.css(s,(d-q.height())+"px");break;case"left":q.css(s,(m.width()+d)+"px");break;case"right":q.css(s,(d-q.width())+"px");break}}})}else{m.animate(p,{duration:r,complete:myatu_bgm.AnimationCompleted})}},NewTopImage:function(a,h,g,f){b("<img>").attr({style:a,"class":"myatu_bgm_fs",id:"myatu_bgm_top",alt:h}).css({visibility:"hidden",width:"",height:""}).appendTo("body");b("#myatu_bgm_top").attr("src",g).imgLoaded(function(){if(typeof f=="function"){f.call(this)}})},SwitchBackground:function(){var m=(background_manager_vars.is_fullsize=="true"),o=(background_manager_vars.is_preview=="true"),l=b("#myatu_bgm_info_tab"),p=(m)?b("#myatu_bgm_top").attr("src"):b("body").css("background-image"),a="",j,n,k;if(o){k=background_manager_vars.image_selection}myatu_bgm.GetAjaxData("select_image",{prev_img:p,selector:k,active_gallery:background_manager_vars.active_gallery},function(d){if(!d){return}myatu_bgm.SetBackgroundLink(d.bg_link);if(m){if(o){j=Number(background_manager_vars.transition_speed);n=background_manager_vars.active_transition;if(n=="random"){n=background_manager_vars.transitions[Math.floor(Math.random()*background_manager_vars.transitions.length)]}}else{j=d.transition_speed;n=d.transition}a=b("#myatu_bgm_top").attr("style");b("#myatu_bgm_top").attr("id","myatu_bgm_prev");myatu_bgm.NewTopImage(a,d.alt,d.url,function(){var f=false;myatu_bgm.AdjustImageSize();if(!b("#myatu_bgm_prev").length){n="none"}b(document).trigger("myatu_bgm_start_transition",[n,j,d]);switch(n){case"none":b(this).css("visibility","");myatu_bgm.AnimationCompleted();break;case"coverdown":f=true;case"slidedown":myatu_bgm.AnimateSlide("top",j,f);break;case"coverup":f=true;case"slideup":myatu_bgm.AnimateSlide("bottom",j,f);break;case"coverright":f=true;case"slideright":myatu_bgm.AnimateSlide("left",j,f);break;case"coverleft":f=true;case"slideleft":myatu_bgm.AnimateSlide("right",j,f);break;case"zoom":b(this).css({display:"none",visibility:""});b("#myatu_bgm_prev").animate({opacity:0},{duration:j,queue:false});b(this).animate({width:b(this).width()*1.05,height:b(this).height()*1.05,opacity:"show",display:"show"},{duration:j,complete:myatu_bgm.AnimationCompleted});break;default:b(this).css({display:"none",visibility:""});b("#myatu_bgm_prev").animate({opacity:0},{duration:j,queue:false});b(this).fadeIn(j,myatu_bgm.AnimationCompleted);break}})}else{b("body").css("background-image",'url("'+d.url+'")');myatu_bgm.SetTimer()}if(l.length){if(b.isFunction(l.qtip)){l.qtip("api").hide()}b(".myatu_bgm_info_tab a").attr("href",d.link);b(".myatu_bgm_info_tab_content img").attr("src",d.thumb);b(".myatu_bgm_info_tab_content h3").text(d.caption);b(".myatu_bgm_info_tab_desc").html(d.desc)}if(b("#myatu_bgm_pin_it_btn").length){var e=b("#myatu_bgm_pin_it_btn iframe").attr("src"),c=d.desc.replace(/(<([^>]+)>)/ig,"");e=myatu_bgm.UrlReplaceQueryArgVal(e,"media",d.url);e=myatu_bgm.UrlReplaceQueryArgVal(e,"description",c);b("#myatu_bgm_pin_it_btn iframe").attr("src",e)}})}});b(document).ready(function(f){var a=f("#myatu_bgm_bg_link"),e=f("#myatu_bgm_info_tab");myatu_bgm.SetTimer();f(window).resize(function(){myatu_bgm.AdjustImageSize()});if(a.length){myatu_bgm.SetBackgroundLink(a.attr("href"));a.remove()}if(f.isFunction(e.qtip)){e.qtip({content:{text:function(d){var c=f(".myatu_bgm_info_tab_content").clone();f("h3",c).remove();if(f(".myatu_bgm_info_tab_desc",c).text()===""){f("img",c).css("margin",0)}else{f("img",c).css({width:"100px",height:"100px"})}return c},title:{text:function(c){return f(".myatu_bgm_info_tab_content:first h3").text()},button:true}},style:{classes:"ui-tooltip-dark ui-tooltip-shadow"},events:{hide:function(c,d){f(".myatu_bgm_info_tab_content:last").remove()}},hide:false,position:{adjust:{x:-10},viewport:f(window)}})}})})(jQuery);