!function(){var a=!1,b=/xyz/.test(function(){})?/\b_super\b/:/.*/;this.Class=function(){},Class.extend=function(c){function d(){!a&&this.init&&this.init.apply(this,arguments)}var e=this.prototype;a=!0;var f=new this;a=!1;for(var g in c)f[g]="function"==typeof c[g]&&"function"==typeof e[g]&&b.test(c[g])?function(a,b){return function(){var c=this._super;this._super=e[a];var d=b.apply(this,arguments);return this._super=c,d}}(g,c[g]):c[g];return d.prototype=f,d.prototype.constructor=d,d.extend=arguments.callee,d}}();