<!DOCTYPE html>
<html>
<body>

<div>
  <h2>Inquiry</h2>
    <div>
      <p>Your following inquiry sent to TokyoWorks.</p>
    </div>
  <div>
    <p><strong>[Details]</strong></p>
    <div><strong>Your name</strong> : {{ $name }}</div>
    <div><strong>Email</strong> : {{ $email }}</div>
    <div><strong>Company</strong> : {{ $company }}</div>
    <div><strong>Inquiry</strong> : </div>
    <div>{!! nl2br(e($inquiry)) !!}</div>
    <div><strong>Postal code</strong> : {{ $postalCode }}</div>
    <div><strong>Address</strong> : {{ $address }}</div>
    
  </div>
  <div>
    <p><strong>[Inquiry Items (JANCODE,QUANTITY) ]</strong></p>
    @foreach($details as $key => $value) 
      <div>{{ $value }}</div>
    @endforeach
    
  </div>

  <div>
    <p>Thank you!</p>
  </div>

</div>

<br/>

</body>
</html>
