<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:70:"E:\wtiao\public/../application/weibao\view\index\search_commodity.html";i:1497009490;}*/ ?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title></title>
		<link rel="stylesheet" type="text/css" href="/static/css/tb_commodity_list.css"/>
		<script type="text/javascript" src="/static/build/flexible.js" ></script>
		<script type="text/javascript" src="/static/js/vue.min.js" ></script>
	</head>
	<body id="search_commodity_body">
		<header>
			<div class="search_commodity">
				<span class="text">宝贝</span>
				<form id="searchForm" action="" method="get">
					<input type="text" name="searchCommodity" id="searchCommodity" value="" />
					<input type="submit" class="confirm_submit" id="confirm_submit" value=""/>
				</form>
			</div>
			<ul class="suggest_sug">
				<li v-for='item in suggest_data'  @click="add_sug($index,item)">{{item}}<div class='add'><div class='icons_suggest_addto'></div></div></li>
			</ul>
		</header>
		<arcitle>
			<nav>
				<ul class="nav_sort">
					<li class="droplist_trigger selected_special">
						<span class="text">综合排序</span>
						<span class="arrow"></span>
						<span class="bar"></span>
					</li>
					<li data-value="_sale">
						<span class="text">销量优先
					</li>
				</ul>
				<div class="sort_contain">
					<ul class="sorts" style="">
						<li class="sort selected">综合排序
							<span class="icons-checked-icon"></span>
						</li>
						<li class="sort" data-value="_bid">价格从高到低
							<span class="icons-checked-icon"></span>
						</li>
						<li class="sort" data-value="bid">价格从低到高
							<span class="icons-checked-icon"></span></li>
						<li class="sort" data-value="_ratesum">信用排序
							<span class="icons-checked-icon"></span>
							
						</li>
					</ul>
				</div>
			</nav>
			<div id="commodity_contain" class="commodity_list">
				<ul>
					<li v-for="item in data.items" @click='lookDetail($event,$index)'>
						<div class="picture"><div v-if='item.url.indexOf("tmall")>0' class="tmTagPic"></div><a href="javascript:;"><img v-bind:src="item.img2" alt="" /></a></div>
						<div class="info">
							<h3 v-text="item.name"></h3>
							<p class="info_price">
								<em >
									<span class="price_icon">¥</span><span class="font_num" v-text="item.price"></span>
								</em>
								<del>
									<span class="price_icon">¥</span><span class="font_num" v-text="item.originalPrice"></span>
								</del>
							</p>
							<div class="info_main">
								<p class="info_freight">免运费</p>
								<p class="info_num">
									<span class="font_num" v-text="item.sold"></span>人付款
								</p>
								<p class="info_area" v-text="item.area"></p>
							</div>
						</div>
					</li>
				</ul>
			</div>
			<div class="sort-droplist-mask" id=""></div>
		</arcitle>
		<footer>
			
		</footer>
		<script type="text/javascript" src="/static/js/zepto.min.js" ></script>
		<script type="text/javascript">
		var data={
			items:[]
		};
		var suggest_data=[];
		//构建Vue实例，
		var vm = new Vue({
			el:"#search_commodity_body",
		  	data:{
		  		data:data,
		  		suggest_data:suggest_data
		  	},
		  	methods:{
		  		lookDetail:function (event,index){
			  		if(data.items[index].url.indexOf("tmall")>0){
			  			var isTm=1;
			  			location.href='/weibao/index/processUrl?isTm='+isTm+'&itemId='+data.items[index].itemNumId+'&price='+data.items[index].price+'&sold='+data.items[index].sold+'&area='+data.items[index].area;
			  		}else{
			  			var isTm=0;
			  			location.href='/weibao/index/processUrl?isTm='+isTm+'&itemId='+data.items[index].itemNumId;
			  		}

			  	},
			  	add_sug:function(index,item){
			  		document.getElementById("searchCommodity").value=this.suggest_data[index];
			  		data.items=[];
			  		requestData();
			  		$(".suggest_sug").hide();
			  	}
		  	}
		})
		//按价格排序
		$(".nav_sort").on("click","li",function(e){
			that=$(this);
			if(that.index()==0){
				$(".sort_contain").toggle();
				$(".sort-droplist-mask").toggle();
				that.addClass("selected_special").siblings().removeClass("selected");
			}else if(that.index()==1){
				$(".sort_contain").hide().find("li").removeClass("selected");
				$(".sort-droplist-mask").hide();
				that.addClass("selected").siblings().removeClass("selected_special");
				page=1;
				data.items=[];
				requestData($(e.target));
			}
		}); 
		//按销量排序
		$(".sorts").on("click","li",function(e){
			$(this).addClass("selected").siblings("li").removeClass("selected");
			$(".sort_contain").toggle();
			$(".sort-droplist-mask").toggle();
			$(".droplist_trigger .text").text($(this).text());
			page=1;
			data.items=[];
			requestData($(e.target));
		});
		
		$("#confirm_submit").click(function(e){
			e.preventDefault();
			page=1;
			data.items=[];
			requestData($(this));
		});
		var keyword = document.getElementById('searchCommodity');
		var script=null;
		var scriptFlag=true;
		var timeflag=null;
		$("#searchCommodity").bind('input propertychange', function() {
			$(".suggest_sug").show();
			if(scriptFlag){
				scriptFlag=false;
				script = document.createElement('script');
	            script.src = "https://suggest.taobao.com/sug?q="+keyword.value+"&code=utf-8&area=c2c&nick=&sid=null&callback=searchJsonp";
	            document.body.appendChild(script);
			    timeFlags = setTimeout(function(){
			    	script.parentNode.removeChild(script);
			    	scriptFlag=true;
			    },200);
			}
            
		
		});
        function searchJsonp(d){
        	suggest_data.splice(0,suggest_data.length);
        	d.result.forEach(function(dom,index){
        		suggest_data.push(dom[0]);
        	});
        }
		function requestData(that){
				var commodityName=document.getElementById("searchCommodity").value;
				var sortType=$(".selected").data("value")||1;
				$.ajax({
					type:"post",
					url:"/weibao/index/GetCommodityData",
					dataType:"json",
					data:{"commodityName":commodityName,"sortType":sortType,"page":page},
				    success:function(res){
	                  data.items=data.items.concat(res.listItem);
	                   if(res.totalPage==0){
							requestData(that);
	                   }else{
	                   	 pageFlag=false;
	                   	 setTimeout(function(){
	                   	 	pageFlag=true;
	                   	 },500)
	                   }
	                },
	                error:function(err){
	                   console.log(err)
	                }
				});
			}
		var timeFlag=null,
		 	pageFlag=true,
		 	page=1;
		window.addEventListener("scroll",function(){
			clearTimeout(timeFlag);
			if(pageFlag){
				timeFlag = setTimeout(function(){
				 	if($("#commodity_contain ul li:last-child")[0].getBoundingClientRect().top<2000){
				 		page++;
				 		var selected=$(".selected")[0];
				 		requestData($(selected));
				 	}  
				},300);
			}
			
		})
		window.onload=function(){
			if($.trim(document.getElementById("searchCommodity").value) != ""){
				$("#confirm_submit").click();
			}
		}
		</script>
	</body>
</html>
