This terraform file creates a minecraft server on Digital Ocean. It creates
a completely new Minecraft server
To launch it you will need to define 3 variables

* Your Digital Ocean API token
* Location of your SSH private key
* Location of the matching SSH public key

There are two ways of doing it.

* Rename env.sh.sample to env.sh and configure the variables in there. Then source the file with

```source env.sh```

* You can also add them directly into the variables.tf file by doing


Once you are done you should type

```terraform plan```

to see the plan of execution. If that is looking good type

```terraform apply```

to execute it. To find out 

To destroy it type

```terraform destroy```


TODO:

* Import 