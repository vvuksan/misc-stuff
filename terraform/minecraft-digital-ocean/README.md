This terraform file creates a minecraft server on Digital Ocean. It creates
a completely new Minecraft server
To launch it you will need to define 3 variables

* Your Digital Ocean API token (do_token)
* Location of your SSH private key (pvt_key)
* Location of the matching SSH public key (pub_key)

There are two ways of doing it.

* Rename env.sh.sample to env.sh and configure the variables in there. Then source the file with

```source env.sh```

* You can also add them directly into the variables.tf file

Once you are done configuring you should type

```terraform plan```

to see the plan of execution. It should not error out. If that is looking good type

```terraform apply```

to execute it. Once it's all done you should have an instance running in the cloud. To find out 
what IP address it got assigned type

```terraform show | grep ipv4```

To destroy it type

```terraform destroy```


TODO:

* Import minecraft config from elsewhere