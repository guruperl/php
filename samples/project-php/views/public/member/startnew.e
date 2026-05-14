{{ include("start.e") }}
        <h2>New Tabilet Application</h2>
        <p class="lead">Please fill in the form and submit.</p>

          <form class="needs-validation" action="member" method="post">
	    <input type="hidden" name="action" value="insert">
            <h4>Login Information</h4>
<p>
                Login Name: <input type="text" name="login"> (a-z0-9 only, length 4-10, starting with letter)
</p>
<p>
                Password: <input type="password" name="passwd">
                Confirm Password: <input type="password" name="confirm">
</p>
			<h4>Personal Information</h4>
<p>
                First name: <input type="text" name="firstname">
                Last name: <input type="text" name="lastname">
</p>
<p>
                Email: <input type="text" name="email">
                Phone: <input type="text" name="phone">
</p>
			<h4>Mailing Address</h4>
<p>
                Street: <input type="text" name="street">
                City: <input type="text" name="city">
</p>
<p>
                State:
                <select name="state">
                  <option value="">Choose...</option>
                  <option value="AL">Alabama</option>
                  <option value="AK">Alaska</option>
                  <option value="AZ">Aziona</option>
                  <option value="AR">Arkansas</option>
                  <option value="CA">California</option>
                </select>
                Country:
                <select name="country">
                  <option value="">Choose...</option>
                  <option value="China">China</option>
                  <option value="Canada">Canada</option>
                  <option value="United States">United States</option>
                </select>
                Zip: <input type="text" name="zip">
</p>
<p>
            <input type=submit value="Submit">
</p>
          </form>

{{ include("end.e") }}
