<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:73:"E:\wtiao\public/../application/weibao\view\index\tm_commodity_detail.html";i:1497351427;}*/ ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title></title>
		<link rel="stylesheet" type="text/css" href="/static/css/tm_commodity_detail.css"/>
		<link rel="stylesheet" type="text/css" href="/static/css/swiper-3.4.1.min.css"/>
		<script type="text/javascript" src="/static/build/flexible.js" ></script>
		<script type="text/javascript" src="/static/js/vue.min.js" ></script>
	</head>
	<body id="body_container">
		<arcitle >
			<section>
				<div id="shopBigPic_swiper" class="swiper-container">
				    <div class="swiper-wrapper">
				        <div class="swiper-slide " v-for="item in commodityData.lunboImageUrl">
				        	<img v-bind:src="item"/>
				        </div>
				    </div>
				    <!-- 如果需要分页器 -->
				    <div class="swiper-pagination"></div>
				    
				    <!-- 如果需要滚动条 -->
				    <div class="swiper-scrollbar"></div>
				</div>
				<h1 v-text="commodityData.commodityName">
				</h1>
				<div class="commodity_price">
					<p>￥<span v-text="commodityData.price"></span></p>
					<div class="del_price">
						<span>价格：</span>
						<span>￥</span>
						<del v-text="commodityData.delPrice"></del>
					</div>
				</div>
				<div class="titletab">
					<p >快递:<span v-text="commodityData.freight"></span></p>
					<p>月销<span v-text="commodityData.num"></span>笔</p>
					<p v-text="commodityData.area"></p>
				</div>
				<div v-if="colorTypeFlag" class="choosedsku" @click="showFlag=!showFlag">
					请选择 颜色分类 
				</div>
				<div class="shop_header">
					<div class="shop_img">
						<img v-bind:src="commodityData.shopInfo.head_picture"/>
					</div>
					<div class="shop_info">
						<h3 v-text="commodityData.shopInfo.name"></h3>
					</div>
				</div>
				<div class="shop_score">
					<p></p>
					<p></p>
					<p></p>
				</div>
				<ul class="score">
		            <li v-for="item in commodityData.score" >
		            	<span v-if="$index == 0">描述相符<b :class="item.className"  v-text="item.text"></b></span>
		            	<span v-if="$index == 1">服务态度<b :class="item.className"  v-text="item.text"></b></span>
		            	<span v-if="$index == 2">发货速度<b :class="item.className"  v-text="item.text"></b></span>
		            </li>
		        </ul>
        		<div class="to_shop">
					<a :href="commodityData.allCommodity">全部商品</a>
					<a :href="commodityData.shopUrl">进入店铺</a>
				</div>
				<div id="s-recommend">
					<h3>看了又看</h3>
					<div id="recommend_list">
						<div id="recommend_swiper" class="swiper-container">
						    <div class="swiper-wrapper">
						        <div @click="lookDetail($event,$index,item)" class="swiper-slide " v-for="item in commodityData.recommend">
						        	<div class="recommend_img">
						        		<img :src="item.img"/>
						        	</div>
						        	<p class="title" v-text="item.title"></p>
						        	<p class="del_price" >￥<span v-text="item.marketPrice"></span></p>
						        	<p>￥<span v-text="item.price"></span></p>
						        </div>
						    </div>
						</div>
					</div>
				</div>
			</section>
			<section>
				<div class="subtitle">
			        <h3 class="newAttraction" v-text="commodityData.newAttraction"></h3>
			    </div>
			    <div class="ortherData" >
			    	<div class="dataMoudle" v-for="item in commodityData.ortherData">
			    		<div  v-if="item.moduleKey=='hot_recommanded'">
			    			<h4  v-text="item.moduleName"></h4>
				    		<div class="module_box" >
				    			<div class="module_item" v-for="items in item.data">
				    				<img :src="items.img"/>
				    				<p class="module_title"  v-text="items.title"></p>
				    				<span class="module_price">￥<i v-text="items.price"></i></span>
				    			</div>
				    		</div>
			    		</div>
			    		<div v-else class="dataMoudle shopActive">
				    		<h4 v-text="item.moduleName"></h4>
				    		<a href="#"><img :src="item.data[0].img"/></a>
					    </div>
			    	</div>
			    	<div v-if="commodityData.cd_parameter" class="dataMoudle">
			    		<h4>商品信息</h4>
			    		<ul class="cd_paramete">
			    			<li v-for="item in commodityData.cd_parameter.data.props">
			    				<span v-text="item.ptext"></span>
			    				<p v-text="item.vtexts"></p>
			    			</li>
			    		</ul>
			    	</div>
			    	
			    </div>
				<div class='detail'>
					<h4 class="title">商品图片</h4>
					<ul>
						<li v-for='item in commodityData.pictureDetail'><img v-bind:src='item.img'/></li>
					</ul>
				</div>
			</section>
			<section>
				<div class='rate_single'>
					<ul>
						<li v-for='item in commodityData.assessInfo'>
							<h2><img src='/static/images/TB1yeWeIFXXXXX5XFXXuAZJYXXX-210-210.png_40x40.jpg'/><span v-text='item.displayUserNick'></span></h2>
							<p v-html='item.rateContent'></p>
							<p v-if="item.reply" class="reply"v-text="item.reply"></p>
							<span v-text='item.rateDate'></span>
							<div class='assess_img'><img v-for='img in item.pics' v-bind:src='img'/></div>
						</li>
					</ul>
				</div>
			</section>
		</arcitle>
		<div  id="tab_contain" class="infomain_tab">
			<div id="tabbar" class="tabbar" >
				<a @click="tab($index ,item.view)" v-for="item in tabs" :class="{active:active==$index}" href="javascript:void(0)" v-text="item.name"></a>
			</div>
		</div>
		<div :class="{show:showFlag}" class="sort-droplist-mask" id="J_DroplistMask" v-on:click.self="showFlag=!showFlag">
			<div  class="pop_main ">
				<div class="sku_pro animatied fadeInUp">
					<div class="sku_pro_ct">
						<div class="sku_img">
							<img v-bind:src="skuPics"/>
						</div>
						<div class="sku_pro_info">
							<p class="price" >¥{{skuPrice}}</p>
							<p class="quantity">库存：{{skuStock}}</p>
							<p>请选择：{{commodityData.colorType.text}}</p>
						</div>
					</div>
					<div class="color_type">
						<h2 v-text="commodityData.colorType.text"></h2>
						<ul>
							<li @click='selecyColor($index,item.text,$event)' v-for="item in commodityData.colorType.values">{{item.text}}</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<footer></footer>
		<script type="text/javascript" src="/static/js/zepto.min.js" ></script>
		<script type="text/javascript" src="/static/js/swiper-3.4.1.min.js" ></script>
		<script type="text/javascript">
			try{
				var data=<?php echo $data; ?>;
			}catch(e){
				alert(e+"</br>请求数据失败，请尝试刷新网页");
			}
			var colorTypeFlag=null,skuStock=null,skuPrice=null,skuPics=null,skuName=null,doEclientWidth=-(document.documentElement.getBoundingClientRect().width);
			commodityData={};
			commodityData.shopInfo={};
			commodityData.assessInfo=[];
			commodityData.score=[];
			commodityData.recommend=[];
			commodityData.ortherData=[];
			try{
				eval(data.dataDetail);
			}catch(e){
				console.log(e+"请求数据失败，请尝试刷新网页");
			}
			try{
				for(var i=0;i<data.dataOther.length;i++){
					if(data.dataOther[i].indexOf('_DATA_Detail')||data.dataOther[i].indexOf('_DATA_Mdskip')){
						eval(data.dataOther[i]);
					}
				}
			}catch(e){
				console.log(e+"请求数据_DATA_Mdskip失败，请尝试刷新网页");
			}
			if(_DATA_Detail){
				commodityData.lunboImageUrl=data.imgUrl;
				commodityData.commodityName=_DATA_Detail.itemDO.title;
				commodityData.price=data.price;
				commodityData.delPrice=data.delPrice;
				commodityData.freight="0.00";
				commodityData.area=data.area;
				commodityData.num=data.sold;
				commodityData.shopInfo.name=data.shopName;
				commodityData.shopInfo.head_picture=_DATA_Detail.itemDO.shopIcon;
				commodityData.newAttraction=_DATA_Detail.itemDO.newAttraction;
				commodityData.allCommodity="/weibao/index/getShopCommodity?shopId="+_DATA_Detail.itemDO.userId+"&shopURL="+data.shopUrl;
				commodityData.shopUrl="/weibao/index/getShopData?shopURL=https:"+data.shopUrl;
				commodityData.score=data.score;
			}
			//尝试得到商品标志
			try{
				if(JSON.parse(data.assessFlag).tags) {
					var tags = JSON.parse(data.assessFlag).tags;
					commodityData.assessmentNum=tags.dimenSum;
				}else{
					commodityData.assessmentNum=null;
				};
				commodityData.label=tags.tagClouds;
			}catch(e){
				console.log(e);
			}
			//尝试得到颜色分类
			try{
				if(_DATA_Detail.valItemInfo.skuList){
					colorTypeFlag=true;
					commodityData.valItemInfo=_DATA_Detail.valItemInfo;
					var pvs = ';'+_DATA_Detail.valItemInfo.skuList[0]['pvs']+';';
					skuStock=commodityData.valItemInfo.skuMap[pvs].stock;
					skuPrice=commodityData.valItemInfo.skuMap[pvs].price;
					if(commodityData.valItemInfo.skuPics[pvs]){
						skuPics=commodityData.valItemInfo.skuPics[pvs];
					}else{
						skuPics=data.imgUrl[0];
					}
					
					skuName=commodityData.valItemInfo.skuName[0].values[0].text;
					commodityData.colorType=_DATA_Detail.valItemInfo.skuName[0];
				}else{
					colorTypeFlag=false;
				}
			}catch(e){
				console.log(e);
			}
			//尝试得到商品详情
			try{
				console.log(_DATA_Detail.api);
				if(_DATA_Detail.api.newWapDescJson){
					_DATA_Detail.api.newWapDescJson.forEach(function(item,index){
						if(item.moduleName=='商品图片'){
							commodityData.pictureDetail=item.data;
						}
					});
				}else{
					commodityData.pictureDetail="";
				}
				
			}catch(e){
				console.log(e);
			}
			//尝试得到商品信息
			try{
				if(data.cd_parameter){
					commodityData.cd_parameter=JSON.parse(data.cd_parameter);
				}
			}catch(e){
				console.log(e);
			}
			
			//构建Vue实例，
			var vm = new Vue({
				el:"#body_container",
			  	data:{
			  		currentView:"detail",
			  		active: 0,
			  		showFlag:false,
			  		colorTypeFlag:colorTypeFlag,
			  		skuStock:skuStock,
			  		skuPrice:skuPrice,
			  		skuPics:skuPics,
			  		skuName:skuName,
					tabs: [   
					    {       
					       name: '基本信息',   
					       view: 'detail'  
					    },     
					    {       
					       name: '商品详情',    
					       view: 'parameter'    
					    },
					    {       
					       name: '评价',    
					       view: 'recommend'    
					    }  
				    ],
				    commodityData:commodityData
				},
				
				methods:{
					tab:function(i,v){
						 this.active = i;
  						 if(i==1){
						 	$("arcitle").css({
						 		"-webkit-transform":"translate3d("+doEclientWidth+"px, 0px, 0px)",
						 		"transform":"translate3d("+doEclientWidth+"px, 0px, 0px)"
						 	});
						 	
						 }else if(i==2){
  						 	$("arcitle").css({
						 		"-webkit-transform":"translate3d("+2*doEclientWidth+"px, 0px, 0px)",
						 		"transform":"translate3d("+2*doEclientWidth+"px, 0px)"
						 	});
  						 }else{
  						 	$("arcitle").css({
						 		"-webkit-transform":"translate3d(0px, 0px, 0px)",
						 		"transform":"translate3d(0px, 0px, 0px)"
						 	});
  						 }
  						 $("section").eq(i).css({
						 		"overflow":"auto",
						 		"height":"auto"
						 	}).siblings().css({
						 		"overflow":"hidden",
						 		"height":"16.693333rem"
						 	});
					},
					isShow:function(){
						this.showFlag=true;
					},
					selecyColor:function(i,v,e){
						var id=';'+_DATA_Detail.valItemInfo.skuList[i].pvs+';';
						this.skuStock=commodityData.valItemInfo.skuMap[id].stock;
				  		this.skuPrice=commodityData.valItemInfo.skuMap[id].price;
				  		this.skuName=v;
				  		if(commodityData.valItemInfo.skuPics[id]){
				  			this.skuPics=commodityData.valItemInfo.skuPics[id];
				  		}
						$(e.target).css({
							"border-color": "#b10000",
							"color": "#000"
						}).siblings().css({
							"border-color": "#e5e5e5",
							"color": "#000"
						})
					},
					score:function(i){
						return true;
					},
					lookDetail:function (event,index,item){
				  		if(commodityData.recommend[index].url.indexOf("tmall")>0){
				  			var isTm=1;
				  						location.href='/weibao/index/processUrl?isTm='+isTm+'&itemId='+item.id+'&price='+item.price+'&sold=--&area=--';
				  		}else{
				  			var isTm=0;
				  			location.href='/weibao/index/processUrl?isTm='+isTm+'&itemId='+item.id;
				  		}
	
				  	}
				}
			})
			var accessFlag=true;
			var page=1;
			function getAccess(){
				if(accessFlag){
					page++;
					var getAccess={
				 		"isTm":data.isTm,
				 		"sellerID":_DATA_Detail.itemDO.userId,
				 		"itemID":_DATA_Detail.itemDO.itemId,
				 		'page':page
				 	};
				 	$.ajax({
				 		type:"post",
				 		url:"/weibao/index/GetAccessData",
				 		async:true,
				 		data:getAccess,
				 		success:function(res){
				 			if(JSON.parse(res).rateDetail.rateList){
				 				commodityData.assessInfo=JSON.parse(res).rateDetail.rateList;
				 				accessFlag=false;
				 			}else{
				 				getAccess();
				 			}
				 		},
				 		error:function(err){
				 			alert('请求评价数据失败');
				 			accessFlag=true;
		                }
				 	});
				}
				
			}
			/*scriptAccess = document.createElement('script');
            scriptAccess.src = "https://rate.tmall.com/list_detail_rate.htm?itemId='.$_POST['itemID'].'&sellerId='.$_POST['sellerID'].'&order=3&append=0&content=0&currentPage='.$_POST['page'].'&pageSize=10&tagId=&_ksTS=1489809936348_512&callback=jsonp3";
            document.body.appendChild(scriptAccess);
            setTimeout(function(){
            	scriptAccess.parentNode.removeChild(scriptAccess);
            },1000)*/
		</script>
		<script type="text/javascript">
			
			/*var scriptRecommend=null,scriptTag,scriptAccess=null;
			scriptRecommend = document.createElement('script');
            scriptRecommend.src = "https://aldcdn.tmall.com/recommend.htm?appId=03080&itemId='.$_GET['itemId'].'&categoryId=110206&sellerId='.$_GET['sellerId'].'&resultSize=12&_ksTS=1489806690121_444&callback=jsonp";
            document.body.appendChild(scriptRecommend);
            setTimeout(function(){
            	scriptRecommend.parentNode.removeChild(scriptRecommend);
            },1000)
            function jsonp(a){
            	console.log(a);
            }
            function jsonp2(a){
            	console.log(a);
            }
            function jsonp3(a){
            	console.log(a);
            }
            $url='https://aldcdn.tmall.com/recommend.htm?appId=03080&itemId='.$_GET['itemId'].'&categoryId=110206&sellerId='.$_GET['sellerId'].'&resultSize=12&_ksTS=1489806690121_444';
		$url2='https://detailskip.taobao.com/json/wap/tmallH5Desc.do?_ksTS=1489820826429_496&callback=setMdskip&itemId='.$_GET['itemId'].'&sellerId='.$_GET['sellerId'].'&isPreview=false&isg=AuDgXeh1rO_KJxDslDcORQ7Gse4-_2Aoq6eCMlrxrPuOVYB_AvmUQ7Zjn0eq&isg2=AsjIpAa3XVo%2FHf0FqOwc%2FzrXGC3acSx7';*/
			function getRecommend(){
				 	$.ajax({
				 		type:"get",
				 		url:"/weibao/index/GetTMRecommend?itemId="+_DATA_Detail.itemDO.itemId+"&sellerId="+_DATA_Detail.itemDO.userId,
				 		async:true,
				 		success:function(res){
				 			commodityData.recommend=JSON.parse(JSON.parse(res)[0]).list;
				 			commodityData.ortherData=JSON.parse(JSON.parse(res)[1]);
				 			setTimeout(function(){
				 				var recommend_swiper = new Swiper ('#recommend_swiper', {
								    direction: 'horizontal',
								    loop: false,
								    slidesPerView:3, //分组显示 
								    spaceBetween:30
								});
				 			},1000);
				 		},
				 		error:function(err){
				 			alert('请求评价数据失败，可以尝试点击评价继续请求');
				 			accessFlag=true;
		                }
				 	});
				
			}
			getRecommend();
			getAccess();
		</script>
		<script type="text/javascript">
			document.getElementById("J_DroplistMask").ontouchmove=function(e){
				return false;
			};
			//借用swiper插件，图片轮播
			var mySwiper = new Swiper ('#shopBigPic_swiper', {
			    direction: 'horizontal',
			    loop: true,
			    
			    // 如果需要分页器
			    pagination: '.swiper-pagination',
			    
			    // 如果需要滚动条
			    scrollbar: '.swiper-scrollbar',
			});
		</script>
		
	</body>
</html>
