<template>
<div>
<h3>{{ names.error_code }}</h3>
<FORM id="admin_login" @submit.prevent="submit">
<pre>
用户名：<INPUT TYPE="TEXT"     name="login" />
密码：  <INPUT TYPE="PASSWORD" name="passwd" />
<button TYPE="SUBMIT"> 登陆 </button>
</pre>
</FORM>
</div>
</template>

<script>
module.exports = {
  props: ['comp', 'names'],
  methods: {
    submit: function(e) {
      var f = document.getElementById("admin_login");
      var formData = new FormData(f);
      formData.append("role","admin");
      formData.append("tag","json");
      $scope.login('admin', 'db', Object.fromEntries(formData), {role:'admin', comp:'admin', action:'topics'})
    }
  }
}
</script>
