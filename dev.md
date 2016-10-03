##Some terms:
**Developer workstation**: developer's computer where developer is modifying the code.

**Repository**: GitHub, Bitbucket, etc, i.e. code storage.

**Server**: remote machine with site, i.e. product server. Any failures there kill ya. Just sayin'.

##On developer workstation
1) Edit the module code in master branch, then commit changes:

`git commit -am "<Few words about changes in this commit>"`

2) If someone changed a code in the repository -- we have to get these changes:

`git pull --commit`

3) Switch to 'product' branch:

`git checkout product`

If no branch 'product' exists on current machine, add flag -b:

`git checkout -b product`

4) Merge branches:

`git merge --commit master`

5) Push updates to repository:

`git push --all`

##On server

*SSH is really cool thing, so we'll use it.*

###Deploy

`git clone -b product https://alyashenko@bitbucket.org/serfcompany/amazing-cart-api-plugin-for-magento.git Amazingcard`

###Update

`git pull --commit`