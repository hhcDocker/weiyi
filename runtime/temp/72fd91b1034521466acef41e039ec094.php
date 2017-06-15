<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:73:"E:\wtiao\public/../application/weibao\view\index\tb_commodity_detail.html";i:1497353827;}*/ ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>商品详情</title>
		<link rel="stylesheet" type="text/css" href="/static/css/tb_commodity_detail.css"/>
		<link rel="stylesheet" type="text/css" href="/static/css/swiper-3.4.1.min.css"/>
		<script type="text/javascript" src="/static/build/flexible.js" ></script>
	</head>
	<body>
		<arcitle id="body_container">
			<div class="back">
				
			</div>
			<div class="swiper-container">
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
				<p><span v-text="commodityData.price"></span></p>
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
			<div class="labelExtra">
				<span v-text="item.title" v-for="item in commodityData.labelExtra" v-show="$index<3">8天退货</span>
			</div>
			<div v-if="colorType" class="choosedsku" @click="showFlag=!showFlag">
				请选择 颜色分类 
			</div>
			<div class="rate_header">
				<h3>宝贝评价(<span v-text="commodityData.assessmentNum"></span>)</h3>
			</div>
			<div class="rate_tags" >
				<p v-for="item in commodityData.label"><span v-text="item.word"></span><span>+</span><span v-text="item.count"></span></p>
			</div>
			<div class="shop_header">
				<div class="shop_img">
					<img v-bind:src="commodityData.shopInfo.shopIcon"/>
				</div>
				<div class="shop_info">
					<a :href="commodityData.shopInfo.shopUrl"><h3 v-text="commodityData.shopInfo.shopName"></h3></a>
				</div>
			</div>
			<div class="shop_header">
				<div v-if='commodityData.shopInfo.allItemCount' class="shop_info_item" >
					<p v-text="commodityData.shopInfo.allItemCount"></p>
					<h3>全部宝贝</h3>
				</div>
				<div v-if='commodityData.shopInfo.newItemCount' class="shop_info_item" >
					<p v-text="commodityData.shopInfo.newItemCount"></p>
					<h3>上新宝贝</h3>
				</div>
				<div v-if='commodityData.shopInfo.fans' class="shop_info_item" >
					<p v-text="commodityData.shopInfo.fans"></p>
					<h3>关注人数</h3>
				</div>
			</div>
			<div class="to_shop_tb">
				<a :href="commodityData.shopUrl">进入店铺</a>
			</div>
			<div  id="tab_contain" class="infomain_tab">
				<div id="tabbar" class="tabbar" >
					<a @click="tab($index ,item.view)" v-for="item in tabs" :class="{active:active==$index}" href="javascript:void(0)" v-text="item.name"></a>
				</div>
				<keep-alive>
					<component :is="currentView" v-bind:comdata="commodityData"></component>
				</keep-alive>
				<div class="load_access_data" v-show="loadingFlag">
					<i></i><span>正在加载数据...</span>
				</div>
			</div>
			<div :class="{show:showFlag}" class="sort-droplist-mask" id="J_DroplistMask" v-on:click.self="showFlag=!showFlag">
				<div  class="pop_main ">
					<div class="sku_pro animatied fadeInUp">
						<div class="sku_pro_ct">
							<div class="sku_img">
								<img v-bind:src="skuPic"/>
							</div>
							<div class="sku_pro_info">
								<p class="price" >￥{{skuPrice}}</p>
								<p class="quantity">库存：{{skuStock}}</p>
								<p>{{skuName}}</p>
							</div>
						</div>
						<div class="color_type">
							<h2>颜色分类</h2>
							<ul>
								<li @click="selectType($index,item,$event)" v-for="item in commodityData.colorType.quantity">{{item.name}}</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</arcitle>
		<footer></footer>
		<script type="text/javascript" src="/static/js/vue.min.js" ></script>
		<script type="text/javascript" src="/static/js/zepto.min.js" ></script>
		<script type="text/javascript" src="/static/js/swiper-3.4.1.min.js" ></script>
		<script type="text/javascript">
			var vm=null,data=null,scriptDetail=null,scriptAccess=null;
			function jsonp(d){
	        	data=d;
	        	if(data.data.apiStack[0].value){
			  		try{
			  			var apiStackValue = JSON.parse(data.data.apiStack[0].value);
			  		}catch(e){
			  			console.log(e);
			  		}
			  		
			  	}
			  	if(data.data.mockData){
			  		try{
			  			var mockData =JSON.parse(data.data.mockData);
			  		}catch(e){
			  			console.log(e);
			  		}
	
			  	}
				try{
					commodityData={};
					commodityData.assessInfo=[];
					commodityData.colorType={};
					commodityData.colorType.quantity=[];
					commodityData.labelExtra=[];
					commodityData.commodityParameter=[];
					commodityData.pictureDetail=[
						{src:"/static/images/577a22deN8423f185.jpg"},
						{src:"/static/images/577a22deN8423f185.jpg"},
						{src:"/static/images/577a22deN8423f185.jpg"},
						{src:"/static/images/577a22deN8423f185.jpg"},
						{src:"/static/images/577a22deN8423f185.jpg"},
						{src:"/static/images/577a22deN8423f185.jpg"}
					];
					commodityData.lunboImageUrl=data.data.item.images;
					commodityData.commodityName=data.data.item.title;
					commodityData.price=apiStackValue.price.price.priceText;
					commodityData.delPrice=mockData.price.price.priceText;
					commodityData.freight=apiStackValue.delivery.postage;
					commodityData.num=apiStackValue.item.sellCount;
					commodityData.area=apiStackValue.delivery.from;
					commodityData.labelExtra=apiStackValue.consumerProtection.items;
					commodityData.label=data.data.rate.keywords;
					commodityData.assessmentNum=data.data.item.commentCount;
					commodityData.shopInfo=data.data.seller;
					commodityData.shopUrl= getShopUrl(commodityData.shopInfo.shopUrl);
					
				}catch(e){
					//TODO handle the exception
					console.log(e);
				}
				try{
					//产品参数
					data.data.props.groupProps[0]['基本信息'].forEach(function(v,d){
						for(var a in v){
							commodityData.commodityParameter[d]={};
							commodityData.commodityParameter[d].left=a;
							commodityData.commodityParameter[d].right=v[a];
						}
						
					});
				}catch(e){
					//TODO handle the exception
					console.log(e);
				}
				try{
					//选择颜色分类
					if(data.data.skuBase.props){
						for(var a in apiStackValue.skuCore.sku2info){
							if(a!=0){
								commodityData.colorType.quantity.push({
									"price":apiStackValue.skuCore.sku2info[a].price.priceText,
									"quantity":apiStackValue.skuCore.sku2info[a].quantity
								});
								
							}
						}
						data.data.skuBase.props[0].values.forEach(function(v,d){
							commodityData.colorType.quantity[d].imgUrl=v.image;
							commodityData.colorType.quantity[d].name=v.name;
						});
					}else{
						colorType=false;
					}
				}catch(e){
					//TODO handle the exception
					console.log(e);
				}
				
				
				//构建Vue实例，
				 vm = new Vue({
					el:"#body_container",
				  	data:{
				  		currentView:"detail",
				  		active: 0,
				  		showFlag:false,
				  		skuPrice:apiStackValue.skuCore.sku2info['0'].price.priceText,
				  		skuStock:apiStackValue.skuCore.sku2info['0'].quantity,
				  		skuPic:data.data.item.images[0],
				  		skuName:apiStackValue.item.skuText,
				  		colorType:colorType,
				  		loadingFlag:false,
						tabs: [   
						    {       
						       name: '图文详情',   
						       view: 'detail'  
						    },     
						    {       
						       name: '产品参数',    
						       view: 'parameter'    
						    },
						    {       
						       name: '评价列表',    
						       view: 'recommend'    
						    }  
					    ],
					    commodityData:commodityData
					},
					
					methods:{
						
						//tab切换触发事件
						tab:function(i,v){
							 this.active = i;
	  						 this.currentView = v;
	  						 if(i==2){
	  						 	getAccess();
	  						 }
						},
						//选择颜色类型toggle
						isShow:function(){
							this.showFlag=true;
						},
						//选择颜色类型触发事件
						selectType:function(i,t,e){
							$(e.target).addClass('active_li').siblings().removeClass("active_li");
							this.skuPrice=t.price;
							this.skuStock=t.quantity;
							this.skuPic=t.imgUrl;
							this.skuName=commodityData.colorType.quantity[i].name;
						}
					},
					//定义template
					components:{
						//图文详情template
						"detail":{
							props:['comdata'],
							template: "<div class='detail'>"+
								"<ul>"+
									"<li v-for='item in comdata.pictureDetail'><img v-bind:src='item.src'/></li>"+
								"</ul>"+
							"</div>"
						},
						//产品参数template
						"parameter":{
							props:['comdata'],
							template: "<div class='parameter'>"+
								"<dl v-for='item in comdata.commodityParameter'><dt>{{item.left}}</dt><dd>{{item.right}}</dd></dl></div>"
						},
						//评价列表template
						"recommend":{
							props:['comdata'],
							template:"<div class='rate_single'>"+
								"<ul>"+
									"<li v-for='item in comdata.assessInfo'>"+
										"<h2><img src='/static/images/TB1yeWeIFXXXXX5XFXXuAZJYXXX-210-210.png_40x40.jpg'/><span v-text='item.user.nick'></span></h2>"+
										"<p v-text='item.content'></p>"+
										"<span v-text='item.date'></span>"+
										"<div class='assess_img'><img v-for='img in item.photos' v-bind:src='img.thumbnail'/></div>"+
									"</li>"+
								"</ul>"+
							"</div>"
						}
					}
				});
				
				scriptAccess = document.createElement('script');
	            scriptAccess.src = "https://rate.taobao.com/feedRateList.htm?userNumId="+data.data.seller.shopId+"&auctionNumId="+data.data.item.itemId+"&currentPageNum=1&callback=jsonp2";
	            document.body.appendChild(scriptAccess);
	            setTimeout(function(){
	            	scriptAccess.parentNode.removeChild(scriptAccess);
	            },1000)
				
	        }
			
			function jsonp2(a){
				commodityData.assessInfo=a.comments;
				accessFlag=false;
	 			vm.loadingFlag=false;
			}
			try{
				data=<?php echo $data; ?>,
			  	colorType=true,
			  	accessFlag=true;
			  	scriptDetail = document.createElement('script');
	            scriptDetail.src = "https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&callback=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22"+data+"%22%7D";
	            document.body.appendChild(scriptDetail);
	            setTimeout(function(){
	            	scriptDetail.parentNode.removeChild(scriptDetail);
	            },1000)
			}catch(e){
				alert(e+"</br请求数据失败，请尝试刷新网页");
			}
			//请求评价数据
			/*function getAccess(){
				if(accessFlag){
  					vm.loadingFlag=true;
					var getAccess={
				 		"sellerID":data.data.seller.shopId,
				 		"itemID":data.data.item.itemId
					 };
				 	$.ajax({
				 		type:"post",
				 		url:"/weibao/index/GetTBAccessData",
				 		async:true,
				 		data:getAccess,
				 		success:function(res){
				 			
				 		},
				 		error:function(err){
				 			alert('请求数据失败');
				 			accessFlag=true;
				 			vm.loadingFlag=false;
		                }
				 	});
				}
				
			};*/
			//请求图文详情数据
			function getAccess(){
				if(accessFlag){
  					vm.loadingFlag=true;
					var getAccess={
				 		"sellerID":data.data.seller.shopId,
				 		"itemID":data.data.item.itemId
					 };
				 	$.ajax({
				 		type:"post",
				 		url:"/weibao/index/GetTBAccessData",
				 		async:true,
				 		data:getAccess,
				 		success:function(res){
				 			res=JSON.parse(res);
							commodityData.assessInfo=res.comments;
							accessFlag=false;
				 			vm.loadingFlag=false;
				 		},
				 		error:function(err){
				 			alert('请求数据失败');
				 			accessFlag=true;
				 			vm.loadingFlag=false;
		                }
				 	});
				}
				
			};
			function getShopUrl(url){
				var str=url;
				return "/weibao/index/getShopData?shopURL=https://shop.m.taobao.com/shop/shop_index.htm"+encodeURIComponent(str.slice(str.indexOf('?'))); 
			}
		</script>
		<script type="text/javascript">
		//借用swiper插件，图片轮播
		var mySwiper = new Swiper ('.swiper-container', {
		    direction: 'horizontal',
		    loop: true,
		    
		    // 如果需要分页器
		    pagination: '.swiper-pagination',
		    
		    // 如果需要滚动条
		    scrollbar: '.swiper-scrollbar',
		})
		  
		//添加scroll事件,监听页面滚动
	  	window.addEventListener('scroll',winScroll);
	  	var scrollFlag=true;
		function winScroll(e){
		 if(document.getElementById("tab_contain").getBoundingClientRect().top<-24&&scroll){
		  	scrollFlag=false;
		  	document.getElementById("tabbar").className="tabbar tabbar_fixed"
		  	document.getElementById("tab_contain").style.paddingTop="1.013333rem";
		  }else{
		  	scrollFlag=true;
		  	document.getElementById("tabbar").className="tabbar";
		  	document.getElementById("tab_contain").style.paddingTop="0";
		  }
		  
		}
		$(".back").click(function(){
			window.history.go(-1);
		});
		</script>
	</body>
</html>
