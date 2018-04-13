import urllib2
import json, requests
import io

def testCall():
	url = 'https://www.swipetobites.com/twilio/?'

	params = dict(ordid='ord4htavf10zk11',
	cname='George',
	cnum='%2B18034791475',
	ord='curry%20chicken',
	ord_add='no%20cheese',
	rname='hello%20hungry',
	rnum='%2B18034791475',
	pinfo='1928'
	)

	resp = requests.get(url=url,params=params)
	data = json.lodas(resp.txt)

	print (data)

def testFullURL():
	url = 'https://www.swipetobites.com/twilio/?ordid=ord4htavf10zk11&cname=George&cnum=%2B18034791475&ord=curry%20chicken&ord_add=no%20cheese&rname=hello%20hungry&rnum=%2B18034791475&pinfo=1928'

	resp = requests.get(url=url,verify=True)
	data = json.loads(resp.txt)
	print(data)
testFullURL()

