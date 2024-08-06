<!-- resources/views/emails/contract.blade.php -->

<p>Hello Volunter</p>


<ul>
    <li>Email: {{ $data['email'] }}</li>

    <li>Company Name: {{ $data['companyName'] }}</li>

    <li>Message Content: {{ $data['data'] }}</li>
</ul>

<p>Best regards,</p>