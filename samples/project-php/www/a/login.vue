<template>
<div>
<h3>{{ names.error_code }}</h3>
<FORM id="a_login" @submit.prevent="sendit">
<pre>
用户名：<INPUT TYPE="TEXT"     v-model="f0.login" />
密码：  <INPUT TYPE="PASSWORD" v-model="f0.passwd" />
<button TYPE="SUBMIT"> 登陆 </button>
</pre>
</FORM>
</div>
</template>

<script>
module.exports = {
  props: ['names'],
  data: function() {
	return { f0: {} }
  },
  methods: {
    sendit: function(e) {
      this.f0["role"] = "a";
      this.f0["tag"] = "json";
      $scope.login('a', 'car', 'topics', this.f0);
    }
  }
}
</script>
