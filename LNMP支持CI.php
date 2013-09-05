<?php
echo <<<CCTV"
配置nginx支持pathinfo功能
    nginx pathinfo
    nginx模式不支持pathinfo模式，类似info.php/hello形式的url会被提示找不到页面。
    下面的通过正则找出实际文件路径和pathinfo部分的方法，让nginx支持pathinfo。

    location ~ \.php$ {
    root           html;
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;

    ##通过设置模拟出pathinfo
    set $path_info “”;
    set $real_script_name $fastcgi_script_name;
    if ($fastcgi_script_name ~ “^(.+?\.php)(/.+)$”) {
    set $real_script_name $1;
    set $path_info $2;
    }
    fastcgi_param SCRIPT_FILENAME $document_root$real_script_name;
    fastcgi_param SCRIPT_NAME $real_script_name;
    fastcgi_param PATH_INFO $path_info;

    include        fastcgi_params;
    }

    要点：

    1.~ \.php 后面不能有$  以便能匹配所有 *.php/* 形式的url

    2. 通过设置更改 SCRIPT_FILENAME

    我在实际使用张将这段代码融合到了fastcgi_params中。下面是我的nginx配置文件示例：

    配置虚拟主机部分，支持pathinfo的nginx代码如下：

    ## 在nginx.conf的server部分：
    server {
     listen       8080;
     server_name  localhost;

     location ~ \.php {
      include        fastcgi.conf;
     }
    }
    ##要点：  \.php 后面没有$，以便匹配所有 *.php/* 形式
    ##重点代码见 fastcgi.conf 开头部分


    fastcgi.conf 代码如下：

        fastcgi_pass   127.0.0.1:9000;
        ##fastcgi_index  index.php;

        set $path_info "";
        set $real_script_name $fastcgi_script_name;
        if ($fastcgi_script_name ~ "^(.+?\.php)(/.+)$") {
                set $real_script_name $1;
                set $path_info $2;
        }
        fastcgi_param SCRIPT_FILENAME $document_root$real_script_name;
        fastcgi_param SCRIPT_NAME $real_script_name;
        fastcgi_param PATH_INFO $path_info;
        ## 以上是支持pathinfo的重点部分

        fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
        fastcgi_param  SERVER_SOFTWARE    nginx;

        fastcgi_param  QUERY_STRING       $query_string;
        fastcgi_param  REQUEST_METHOD     $request_method;
        fastcgi_param  CONTENT_TYPE       $content_type;
        fastcgi_param  CONTENT_LENGTH     $content_length;

        #fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
        #fastcgi_param  SCRIPT_NAME        $fastcgi_script_name; 这两行是需要注释掉的。请注意
        fastcgi_param  REQUEST_URI        $request_uri;
        fastcgi_param  DOCUMENT_URI       $document_uri;
        fastcgi_param  DOCUMENT_ROOT      $document_root;
        fastcgi_param  SERVER_PROTOCOL    $server_protocol;

        fastcgi_param  REMOTE_ADDR        $remote_addr;
        fastcgi_param  REMOTE_PORT        $remote_port;
        fastcgi_param  SERVER_ADDR        $server_addr;
        fastcgi_param  SERVER_PORT        $server_port;
        fastcgi_param  SERVER_NAME        $server_name;

        # PHP only, required if PHP was built with --enable-force-cgi-redirect
        #fastcgi_param  REDIRECT_STATUS    200;"
        
        my.wed
        ................................................
        
        server
{
listen       80;
server_name  www.touchopenid.com;
index index.html index.htm index.php;
root  /data0/htdocs/openid;

location ~ \.php($|/) {
set  $script     $uri;
set  $path_info  "";
if ($uri ~ "^(.+\.php)(/.+)") {
set  $script     $1;
set  $path_info  $2;
}
fastcgi_pass   127.0.0.1:9000;
include        fastcgi_params;
fastcgi_param  PATH_INFO                $path_info;
fastcgi_param  SCRIPT_FILENAME          $document_root$script;
fastcgi_param  SCRIPT_NAME              $script;
}
.................................................................................

CCTV;
?>
# nginx : location 语法解释

<?php
location

语法:location [=|~|~*|^~] /uri/ { … }
默认:否

上下文:server

这个指令随URL不同而接受不同的结构。你可以配置使用常规字符串和正则表达式。如果使用正则表达式，
你必须使用 ~* 前缀选择不区分大小写的匹配或者 ~ 选择区分大小写的匹配。

确定 哪个location 指令匹配一个特定指令，常规字符串第一个测试。常规字符串匹配请求的开始部分并且区分大小写，
最明确的匹配将会被使用（查看下文明白 nginx 怎么确定它）。然后正则表达式按照配置文件里的顺序测试。
找到第一个比配的正则表达式将停止搜索。如果没有找到匹配的正则表达式，使用常规字符串的结果。

有两个方法修改这个行为。第一个方法是使用 “=”前缀，将只执行严格匹配。如果这个查询匹配，
那么将停止搜索并立即处理这个请求。例子：如果经常发生”/”请求，那么使用 “location = /” 将加速处理这个请求。

第二个是使用 ^~ 前缀。如果把这个前缀用于一个常规字符串那么告诉nginx 如果路径匹配那么不测试正则表达式。

而且它重要在于 NGINX 做比较没有 URL 编码，所以如果你有一个 URL 链接’/images/%20/test’ , 
那么使用 “images/ /test” 限定location。

总结，指令按下列顺序被接受:
1. = 前缀的指令严格匹配这个查询。如果找到，停止搜索。
2. 剩下的常规字符串，长的在前。如果这个匹配使用 ^~ 前缀，搜索停止。
3. 正则表达式，按配置文件里的顺序。
4. 如果第三步产生匹配，则使用这个结果。否则使用第二步的匹配结果。

例子：

location = / {
# 只匹配 / 查询。
[ configuration A ]
}

location / {
# 匹配任何查询，因为所有请求都已 / 开头。但是正则表达式规则和长的块规则将被优先和查询匹配。
[ configuration B ]
}

location ^~ /images/ {
# 匹配任何已 /images/ 开头的任何查询并且停止搜索。任何正则表达式将不会被测试。
[ configuration C ]
}

location ~* \.(gif|jpg|jpeg)$ {
# 匹配任何已 gif、jpg 或 jpeg 结尾的请求。然而所有 /images/ 目录的请求将使用 Configuration C。
[ configuration D ]
}

例子请求:

/ -> configuration A

/documents/document.html -> configuration B

/images/1.gif -> configuration C

/documents/1.jpg -> configuration D

注意：按任意顺序定义这4个配置结果将仍然一样。"
CCTV1;
?>
